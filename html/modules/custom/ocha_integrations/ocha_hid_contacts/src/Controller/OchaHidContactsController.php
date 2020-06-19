<?php

namespace Drupal\ocha_hid_contacts\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaHidContactsController.
 */
class OchaHidContactsController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_hid_contacts.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_hid_contacts.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_hid_contacts:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_hid_contacts';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_hid_contacts';

  /**
   * {@inheritdoc}
   */
  protected static $staticCache;

  /**
   * Get API data.
   */
  public function getApiDataFromEndpoint() {
    $api_endpoint = $this->config->get('api.endpoint');
    $url = $api_endpoint;

    try {
      // Combined data.
      $combined_data = [];

      while (TRUE) {
        $this->loggerFactory->get('ocha_hid_contacts')->notice('Fetching ocha_hid_contacts from @url', [
          '@url' => $url,
        ]);

        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() === 200) {
          $raw = $response->getBody()->getContents();
          $data = json_decode($raw);

          if (empty($data->data)) {
            break;
          }

          $combined_data = array_merge($combined_data, $data->data);

          if (isset($data->next) && isset($data->next->href)) {
            $url = $data->next->href;
          }
          else {
            break;
          }
        }
        else {
          $this->loggerFactory->get('ocha_hid_contacts')->error('Fetching ocha_hid_contacts failed with @status', [
            '@status' => $response->getStatusCode(),
          ]);

          return [];
        }
      }

      $data = $this->fillCache($combined_data);
      $this->saveToJson($data);
      return $data;
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_hid_contacts')->error('Exception while fetching ocha_hid_contacts with @status', [
        '@status' => $exception->getMessage(),
      ]);

      return [];
    }
  }

  /**
   * Fill cache.
   */
  private function fillCache($data) {
    // Key data by id.
    $keyed_data = [];
    foreach ($data as $row) {
      $keyed_data[$row->id] = (object) [
        'id' => $row->id,
        'name' => trim($row->label),
        'href' => $row->self,
        'global_cluster' => isset($row->global_cluster) ? $row->global_cluster : [],
        'lead_agencies' => $row->lead_agencies,
        'partners' => $row->partners,
        'activation_document' => isset($row->activation_document) ? $row->activation_document : [],
        'operations' => $row->operation,
      ];
    }

    $this->loggerFactory->get('ocha_hid_contacts')->notice('Ocha HID Contacts imported, got @num local groups', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer local groups then last time.
    if (count($keyed_data) < $this->state->get('ocha_hid_contacts_count')) {
      $this->loggerFactory->get('ocha_hid_contacts')->error('We had @before local groups before, now only @after', [
        '@before' => $this->state->get('ocha_hid_contacts_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_hid_contacts_count', count($keyed_data));

    if (!empty($keyed_data)) {
      $this->populateCache($keyed_data);
    }

    return $keyed_data;
  }

  /**
   * Get allowed values.
   */
  public function getAllowedValues() {
    $data = $this->getApiData();
    $options = [];

    foreach ($data as $key => $contact) {
      $options[$key] = $contact->name;
      if (isset($contact->organization)) {
        if (isset($contact->organization->acronym)) {
          $options[$key] .= ' - ' . $contact->organization->acronym;
        }
        elseif (isset($contact->organization->name)) {
          $options[$key] .= ' - ' . $contact->organization->name;
        }
      }
    }

    return $options;
  }

  /**
   * Load by HID Id.
   */
  public function loadById($hid_id) {
    $api_endpoint = $this->config->get('api.endpoint');
    $api_key = $this->config->get('api.key');

    if (empty($api_key)) {
      return [];
    }

    $url = $api_endpoint . '/api/v2/user/' . $hid_id;

    $query_string = '';
    $fields = [
      'name',
      'given_name',
      'family_name',
      'email',
      'job_title',
      'organization',
      'phone_number',
      'phone_number_type',
    ];

    foreach ($fields as $field) {
      $query_string .= empty($query_string) ? '' : '&';
      $query_string .= 'fields=' . $field;
    }

    $url .= '?' . $query_string;

    // Add API key.
    $variables['headers'] = ['Authorization' => 'Bearer ' . $api_key];

    $response = $this->httpClient->request('GET', $url, $variables);
    if ($response->getStatusCode() === 200) {
      $raw = $response->getBody()->getContents();
      $data = json_decode($raw);

      // Add to cache.
      $this->appendToCache([$data]);

      return $data;
    }
    else {
      $this->loggerFactory->get('ocha_hid_contacts')->error('Fetching ocha_hid_contacts failed with @status', [
        '@status' => $response->getStatusCode(),
      ]);

      return [];
    }
  }

  /**
   * Find contacts.
   */
  public function autocomplete($search) {
    // Api breaks on < 3 characters.
    if (strlen($search) < 3) {
      return [];
    }

    $api_endpoint = $this->config->get('api.endpoint');
    $api_key = $this->config->get('api.key');

    if (empty($api_key)) {
      return [];
    }

    $url = $api_endpoint . '/api/v2/user';
    $query_string = '';

    $query_vars = [
      'sort' => 'name',
      'limit' => 50,
      'name' => $search,
      'authOnly' => 'false',
    ];

    $fields = [
      'name',
      'given_name',
      'family_name',
      'email',
      'job_title',
      'organization',
      'phone_number',
      'phone_number_type',
    ];

    // Construct querystring based on array.
    foreach ($query_vars as $query_name => $query_var) {
      $query_string .= empty($query_string) ? '' : '&';
      $query_string .= $query_name . '=' . $query_var;
    }
    foreach ($fields as $field) {
      $query_string .= empty($query_string) ? '' : '&';
      $query_string .= 'fields=' . $field;
    }

    $url .= '?' . $query_string;

    // Add API key.
    $variables['headers'] = ['Authorization' => 'Bearer ' . $api_key];

    $response = $this->httpClient->request('GET', $url, $variables);
    if ($response->getStatusCode() === 200) {
      $raw = $response->getBody()->getContents();
      $data = json_decode($raw);

      $this->appendToCache($data);

      return $data;
    }
    else {
      $this->loggerFactory->get('ocha_hid_contacts')->error('Fetching ocha_hid_contacts failed with @status', [
        '@status' => $response->getStatusCode(),
      ]);

      return [];
    }
  }

  /**
   * Append to cache.
   */
  private function appendToCache($data) {
    // Allow updating the cache.
    $keyed_data = $this->getCache();

    // Key data by id.
    foreach ($data as $row) {
      $keyed_data[$row->id] = $row;
    }

    if (!empty($keyed_data)) {
      $this->populateCache($keyed_data);
    }

    return $keyed_data;
  }

}

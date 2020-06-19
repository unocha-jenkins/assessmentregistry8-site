<?php

namespace Drupal\ocha_organizations\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaOrganizationsController.
 */
class OchaOrganizationsController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_organizations.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_organizations.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_organizations:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_organizations';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_organizations';

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
        $this->loggerFactory->get($this->loggerId)->notice('Fetching ocha_organizations from @url', [
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
          $this->loggerFactory->get($this->loggerId)->error('Fetching ocha_organizations failed with @status', [
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
      $this->loggerFactory->get($this->loggerId)->error('Exception while fetching ocha_organizations with @status', [
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
        'name' => trim($row->label),
        'acronym' => isset($row->acronym) ? $row->acronym : '',
        'href' => $row->self,
        'global_cluster' => isset($row->global_cluster) ? $row->global_cluster : NULL,
        'lead_agencies' => isset($row->lead_agencies) ? $row->lead_agencies : [],
        'partners' => isset($row->partners) ? $row->partners : [],
        'activation_document' => isset($row->activation_document) ? $row->activation_document : NULL,
        'operations' => isset($row->operation) ? $row->operation : [],
      ];
    }

    $this->loggerFactory->get($this->loggerId)->notice('OCHA organizations imported, got @num organizations', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer organizations then last time.
    if (count($keyed_data) < $this->state->get('ocha_organizations_count')) {
      $this->loggerFactory->get($this->loggerId)->error('We had @before organizations before, now only @after', [
        '@before' => $this->state->get('ocha_organizations_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_organizations_count', count($keyed_data));

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

    foreach ($data as $key => $value) {
      $options[$key] = $value->name;
      if (isset($value->acronym) && !empty($value->acronym)) {
        $options[$key] .= ' [' . $value->acronym . ']';
      }

      if (!empty($value->operations) && isset($value->operations[0]->label)) {
        $options[$key] .= ' (' . $value->operations[0]->label . ')';
      }
    }

    return $options;
  }

}

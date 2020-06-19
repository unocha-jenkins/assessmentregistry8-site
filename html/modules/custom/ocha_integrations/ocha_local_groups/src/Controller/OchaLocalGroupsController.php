<?php

namespace Drupal\ocha_local_groups\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaLocalGroupsController.
 */
class OchaLocalGroupsController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_local_groups.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_local_groups.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_local_groups:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_local_groups';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_local_groups';

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
        $this->loggerFactory->get('ocha_local_groups')->notice('Fetching ocha_local_groups from @url', [
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
          $this->loggerFactory->get('ocha_local_groups')->error('Fetching ocha_local_groups failed with @status', [
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
      $this->loggerFactory->get('ocha_local_groups')->error('Exception while fetching ocha_local_groups with @status', [
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

    $this->loggerFactory->get('ocha_local_groups')->notice('OCHA local groups imported, got @num local groups', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer local groups then last time.
    if (count($keyed_data) < $this->state->get('ocha_local_groups_count')) {
      $this->loggerFactory->get('ocha_local_groups')->error('We had @before local groups before, now only @after', [
        '@before' => $this->state->get('ocha_local_groups_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_local_groups_count', count($keyed_data));

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
      if (!empty($value->operations) && isset($value->operations[0]->label)) {
        $options[$key] .= ' (' . $value->operations[0]->label . ')';
      }
    }

    return $options;
  }

}

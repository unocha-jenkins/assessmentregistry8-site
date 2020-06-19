<?php

namespace Drupal\ocha_global_coordination_groups\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaGlobalCoordinationGroupsController.
 */
class OchaGlobalCoordinationGroupsController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_global_coordination_groups.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_global_coordination_groups.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_global_coordination_groups:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_global_coordination_groups';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_global_coordination_groups';

  /**
   * {@inheritdoc}
   */
  protected static $staticCache;

  /**
   * Get API data.
   */
  public function getApiDataFromEndpoint() {
    $api_endpoint = $this->config->get('api.endpoint');

    try {
      $this->loggerFactory->get('ocha_global_coordination_groups')->notice('Fetching ocha_global_coordination_groups from @url', [
        '@url' => $api_endpoint,
      ]);

      $response = $this->httpClient->request('GET', $api_endpoint);

      if ($response->getStatusCode() === 200) {
        $raw = $response->getBody()->getContents();
        $data = json_decode($raw);

        $data = $this->fillCache($data);
        $this->saveToJson($data);
        return $data;
      }
      else {
        $this->loggerFactory->get('ocha_global_coordination_groups')->error('Fetching ocha_global_coordination_groups failed with @status', [
          '@status' => $response->getStatusCode(),
        ]);

        return [];
      }
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_global_coordination_groups')->error('Exception while fetching ocha_global_coordination_groups with @status', [
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
    foreach ($data->data as $row) {
      $keyed_data[$row->id] = $row;
    }

    $this->loggerFactory->get('ocha_global_coordination_groups')->notice('OCHA countries imported, got @num countries', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer countries then last time.
    if (count($keyed_data) < $this->state->get('ocha_global_coordination_groups_count')) {
      $this->loggerFactory->get('ocha_global_coordination_groups')->error('We had @before countries before, now only @after', [
        '@before' => $this->state->get('ocha_global_coordination_groups_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_global_coordination_groups_count', count($keyed_data));

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
      $options[$key] = $value->label;
    }

    return $options;
  }

  /**
   * Get item by label.
   */
  public function getItemByLabel($label) {
    $data = $this->getCache();

    foreach ($data as $value) {
      if ($value->label == $label) {
        return $value;
      }
    }

    return FALSE;
  }

}

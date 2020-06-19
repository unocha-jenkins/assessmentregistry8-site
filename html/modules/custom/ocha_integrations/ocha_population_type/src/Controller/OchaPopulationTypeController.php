<?php

namespace Drupal\ocha_population_type\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaPopulationTypeController.
 */
class OchaPopulationTypeController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_population_type.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_population_type.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_population_type:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_population_type';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_population_type';

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
        $this->loggerFactory->get($this->loggerId)->notice('Fetching ocha_population_type from @url', [
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
          $this->loggerFactory->get($this->loggerId)->error('Fetching ocha_population_type failed with @status', [
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
      $this->loggerFactory->get($this->loggerId)->error('Exception while fetching ocha_population_type with @status', [
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
        'global_cluster' => isset($row->global_cluster) ? $row->global_cluster : NULL,
        'lead_agencies' => isset($row->lead_agencies) ? $row->lead_agencies : [],
        'partners' => isset($row->partners) ? $row->partners : [],
        'activation_document' => isset($row->activation_document) ? $row->activation_document : NULL,
        'operations' => isset($row->operation) ? $row->operation : [],
      ];
    }

    $this->loggerFactory->get($this->loggerId)->notice('OCHA population type imported, got @num population type', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer population type then last time.
    if (count($keyed_data) < $this->state->get('ocha_population_type_count')) {
      $this->loggerFactory->get($this->loggerId)->error('We had @before population type before, now only @after', [
        '@before' => $this->state->get('ocha_population_type_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_population_type_count', count($keyed_data));

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

  /**
   * Get item by label.
   */
  public function getItemByLabel($label) {
    $data = $this->getApiData();

    foreach ($data as $value) {
      if ($value->name == $label) {
        return $value;
      }
    }

    return FALSE;
  }

}

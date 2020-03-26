<?php

namespace Drupal\ocha_countries\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaCountriesController.
 */
class OchaCountriesController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_countries.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_countries.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_countries:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_countries';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_countries';

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
      $this->loggerFactory->get('ocha_countries')->notice('Fetching ocha_countries from @url', [
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
        $this->loggerFactory->get('ocha_countries')->error('Fetching ocha_countries failed with @status', [
          '@status' => $response->getStatusCode(),
        ]);

        return [];
      }
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_countries')->error('Exception while fetching ocha_countries with @status', [
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

    $this->loggerFactory->get('ocha_countries')->notice('OCHA countries imported, got @num countries', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer countries then last time.
    if (count($keyed_data) < $this->state->get('ocha_countries_count')) {
      $this->loggerFactory->get('ocha_countries')->error('We had @before countries before, now only @after', [
        '@before' => $this->state->get('ocha_countries_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_countries_count', count($keyed_data));

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
      $options[$key] = $value->label->default;
    }

    return $options;
  }

  /**
   * Get item by label.
   */
  public function getItemByLabel($label) {
    $data = $this->getCache();

    foreach ($data as $value) {
      if ($value->label->default == $label) {
        return $value;
      }
    }

    return FALSE;
  }

}

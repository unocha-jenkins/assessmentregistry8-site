<?php

namespace Drupal\ocha_disasters\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaDisastersController.
 */
class OchaDisastersController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_disasters.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_disasters.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_disasters:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_disasters';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_disasters';

  /**
   * {@inheritdoc}
   */
  protected static $staticCache;

  /**
   * Get API data.
   */
  public function getApiDataFromEndpoint() {
    $api_endpoint = $this->config->get('api.endpoint');

    // Add limit.
    $api_endpoint .= '&limit=200';
    $url = $api_endpoint;

    try {
      // Combined data.
      $combined_data = [];

      while (TRUE) {
        $this->loggerFactory->get('ocha_disasters')->notice('Fetching ocha_disasters from @url', [
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

          if (isset($data->links->next) && isset($data->links->next->href)) {
            $url = $data->links->next->href;
          }
          else {
            break;
          }
        }
        else {
          $this->loggerFactory->get('ocha_disasters')->error('Fetching ocha_disasters failed with @status', [
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
      $this->loggerFactory->get('ocha_disasters')->error('Exception while fetching ocha_disasters with @status', [
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
        'name' => trim($row->fields->name),
        'glide' => isset($row->fields->glide) ? trim($row->fields->glide) : '',
        'status' => trim($row->fields->status),
        'href' => $row->href,
      ];
    }

    $this->loggerFactory->get('ocha_disasters')->notice('OCHA disasters imported, got @num disasters', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer disasters then last time.
    if (count($keyed_data) < $this->state->get('ocha_disasters_count')) {
      $this->loggerFactory->get('ocha_disasters')->error('We had @before disasters before, now only @after', [
        '@before' => $this->state->get('ocha_disasters_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_disasters_count', count($keyed_data));

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
      if (!empty($value->glide)) {
        $options[$key] .= ' (' . $value->glide . ')';
      }
    }

    return $options;
  }

  /**
   * Get item by glide.
   */
  public function getItemByGlide($glide) {
    $data = $this->getCache();

    foreach ($data as $value) {
      if ($value->glide == $glide) {
        return $value;
      }
    }

    return FALSE;
  }

}

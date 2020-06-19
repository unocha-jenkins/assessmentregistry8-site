<?php

namespace Drupal\ocha_themes\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaThemesController.
 */
class OchaThemesController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_themes.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_themes.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_themes:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_themes';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_themes';

  /**
   * {@inheritdoc}
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
        $this->loggerFactory->get($this->loggerId)->notice('Fetching from @url', [
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
          $this->loggerFactory->get($this->loggerId)->error('Fetching failed with @status', [
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
      $this->loggerFactory->get($this->loggerId)->error('Exception while fetching with @status', [
        '@status' => $exception->getMessage(),
      ]);

      // Return cached data.
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
        'status' => isset($row->fields->status) ? trim($row->fields->status) : '',
        'href' => $row->href,
      ];
    }

    $this->loggerFactory->get($this->loggerId)->notice('OCHA themes imported, got @num themes', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer themes then last time.
    if (count($keyed_data) < $this->state->get('ocha_themes_count')) {
      $this->loggerFactory->get($this->loggerId)->error('We had @before themes before, now only @after', [
        '@before' => $this->state->get('ocha_themes_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_themes_count', count($keyed_data));

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
   * Get item by name.
   */
  public function getItemByName($name) {
    $data = $this->getCache();

    foreach ($data as $value) {
      if ($value->name = $name) {
        return $value;
      }
    }

    return FALSE;
  }

}

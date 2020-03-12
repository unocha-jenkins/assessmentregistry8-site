<?php

namespace Drupal\ocha_locations\Controller;

use Drupal\ocha_integrations\Controller\OchaIntegrationsController;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaLocationsController.
 */
class OchaLocationsController extends OchaIntegrationsController {

  /**
   * {@inheritdoc}
   */
  protected $settingsName = 'ocha_locations.settings';

  /**
   * {@inheritdoc}
   */
  protected $jsonFilename = 'ocha_locations.json';

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'ocha_locations:apiData';

  /**
   * {@inheritdoc}
   */
  protected $cacheTag = 'ocha_locations';

  /**
   * {@inheritdoc}
   */
  protected $loggerId = 'ocha_locations';

  /**
   * Get API data.
   */
  public function getApiDataFromEndpoint() {
    $data = [];
    $api_endpoint = $this->config->get('api.endpoint');
    $admin_levels = [0, 1, 2];

    try {
      foreach ($admin_levels as $admin_level) {
        // Combined data.
        $combined_data = [];

        $ts = $this->state->get('ocha_locations_fetch_and_sync_ts_' . $admin_level);

        // Reset ts if cache is empty.
        if (empty($this->getCache())) {
          $ts = 0;
        }

        $url = $api_endpoint . '?filter[admin_level]=' . $admin_level;
        $url .= '&filter[changed][value]=' . $ts . '&filter[changed][operator]=>';
        $url .= '&sort=changed,id';

        while (TRUE) {
          $this->loggerFactory->get($this->loggerId)->notice('Fetching ocha_locations from @url', [
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
            $this->loggerFactory->get($this->loggerId)->error('Fetching ocha_locations failed with @status', [
              '@status' => $response->getStatusCode(),
            ]);

            // Return cached data.
            return $data;
          }
        }

        if ($admin_level == 0) {
          $data = $this->fillCache($combined_data);
        }
        else {
          $data = $this->appendToCache($combined_data);
        }

        $this->state->set('ocha_locations_fetch_and_sync_ts_' . $admin_level, REQUEST_TIME);
      }

      $this->saveToJson($data);
      return $data;
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get($this->loggerId)->error('Exception while fetching ocha_locations with @status', [
        '@status' => $exception->getMessage(),
      ]);

      return [];
    }
  }

  /**
   * Fill cache.
   */
  private function fillCache($data) {
    // Allow updating the cache.
    $keyed_data = $this->getCache();

    foreach ($data as $row) {
      $keyed_data[$row->id] = (object) [
        'name' => trim($row->label),
        'admin_level' => $row->admin_level,
        'pcode' => trim($row->pcode),
        'iso3' => trim($row->iso3),
        'lat' => trim($row->geolocation->lat),
        'lon' => trim($row->geolocation->lon),
        'parent' => NULL,
        'parents' => [],
        'children' => [],
      ];

      $keyed_data[$row->id]->parents[] = $row->id;
    }

    $this->loggerFactory->get($this->loggerId)->notice('OCHA locations imported, got @num locations', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer locations then last time.
    if (count($keyed_data) < $this->state->get('ocha_locations_count')) {
      $this->loggerFactory->get($this->loggerId)->error('We had @before locations before, now only @after', [
        '@before' => $this->state->get('ocha_locations_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_locations_count', count($keyed_data));

    if (!empty($keyed_data)) {
      $this->populateCache($keyed_data);
    }

    return $keyed_data;
  }

  /**
   * Append to cache.
   */
  private function appendToCache($data) {
    // Allow updating the cache.
    $keyed_data = $this->getCache();

    // Key data by id.
    foreach ($data as $row) {
      $parents = [];
      foreach ($row->parents as $parent) {
        $parents[] = substr($parent, strrpos($parent, '/') + 1);
      }

      // Remove self.
      array_shift($parents);

      // Reverse array.
      $parents = array_reverse($parents);

      // Find lowest level parent.
      $my_parent = &$keyed_data[array_shift($parents)];
      foreach ($parents as $parent) {
        $my_parent = &$my_parent->children[$parent];
      }

      if (isset($row->parent[0]->id)) {
        $my_parent->children[$row->id] = (object) [
          'name' => trim($row->label),
          'admin_level' => $row->admin_level,
          'pcode' => trim($row->pcode),
          'iso3' => trim($row->iso3),
          'lat' => trim($row->geolocation->lat),
          'lon' => trim($row->geolocation->lon),
          'parent' => $row->parent[0]->id,
          'children' => [],
        ];
      }
      else {
        $this->loggerFactory->get($this->loggerId)->notice('Missing parent @num', [
          '@num' => $row->parent[0]->id,
        ]);
      }
    }

    if (!empty($keyed_data)) {
      $this->populateCache($keyed_data);
    }

    return $keyed_data;
  }

  /**
   * Get allowed values.
   */
  public function getAllowedValuesByParent($parent = 0, $grand_parent = 0) {
    $data = $this->getApiData();
    $options = [];

    if ($grand_parent) {
      $data = $data[$grand_parent]->children;
    }

    if ($parent) {
      $data = $data[$parent]->children;
    }

    foreach ($data as $key => $value) {
      $options[$key] = $value->name;
    }

    uasort($options, [$this, 'orderOptions']);

    return $options;
  }

}

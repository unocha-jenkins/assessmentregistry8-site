<?php

namespace Drupal\ocha_locations\Controller;

use Drupal\Core\Cache\Cache;
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
   * {@inheritdoc}
   */
  protected static $staticCache;

  /**
   * {@inheritdoc}
   */
  protected static $staticFlatCache;

  /**
   * Get flat cached data.
   */
  public function getFlatCache() {
    if (isset(static::$staticFlatCache)) {
      return static::$staticFlatCache;
    }

    if ($cache = $this->cacheBackend->get('ocha_locations:apiFlatData')) {
      static::$staticFlatCache = $cache->data;
      return static::$staticFlatCache;
    }

    return [];
  }

  /**
   * Set flat cached data.
   */
  public function setFlatCache($data) {
    // Cache forever.
    $this->cacheBackend->set('ocha_locations:apiFlatData', $data, Cache::PERMANENT);

    // Invalidate cache.
    Cache::invalidateTags([$this->cacheTag]);

    // Update static cache.
    static::$staticFlatCache = $data;
  }

  /**
   * Get API data.
   */
  public function getApiDataFromEndpoint() {
    $data = [];
    $api_endpoint = $this->config->get('api.endpoint');
    $admin_levels = [0, 1, 2, 3];

    try {
      // Reset ts if cache is empty.
      if (empty($this->getCache())) {
        $this->loggerFactory->get($this->loggerId)->notice('Resetting ts');
        foreach ($admin_levels as $admin_level) {
          $this->state->set('ocha_locations_fetch_and_sync_ts_' . $admin_level, 0);
        }
      }

      foreach ($admin_levels as $admin_level) {
        // Combined data.
        $combined_data = [];

        $ts = $this->state->get('ocha_locations_fetch_and_sync_ts_' . $admin_level);

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
    $flat_data = $this->getFlatCache();

    foreach ($data as $row) {
      $keyed_data[$row->id] = (object) [
        'id' => $row->id,
        'name' => trim($row->label),
        'admin_level' => $row->admin_level,
        'pcode' => trim($row->pcode),
        'iso3' => trim($row->iso3),
        'lat' => trim($row->geolocation->lat),
        'lon' => trim($row->geolocation->lon),
        'parent' => NULL,
        'children' => [],
      ];

      // Add to flat cache.
      $flat_data[$row->id] = $keyed_data[$row->id];
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
      $this->setFlatCache($flat_data);
    }

    return $keyed_data;
  }

  /**
   * Append to cache.
   */
  private function appendToCache($data) {
    // Allow updating the cache.
    $keyed_data = $this->getCache();
    $flat_data = $this->getFlatCache();

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
          'id' => $row->id,
          'name' => trim($row->label),
          'admin_level' => $row->admin_level,
          'pcode' => trim($row->pcode),
          'iso3' => trim($row->iso3),
          'lat' => trim($row->geolocation->lat),
          'lon' => trim($row->geolocation->lon),
          'parent' => $row->parent[0]->id,
          'children' => [],
        ];

        // Add to flat cache and add parent as well.
        $flat_data[$row->id] = $my_parent->children[$row->id];
        $flat_data[$my_parent->id] = $my_parent;
      }
      else {
        $this->loggerFactory->get($this->loggerId)->notice('Missing parent @num', [
          '@num' => $row->parent[0]->id,
        ]);
      }
    }

    if (!empty($keyed_data)) {
      $this->populateCache($keyed_data);
      $this->setFlatCache($flat_data);
    }

    return $keyed_data;
  }

  /**
   * Get top level values.
   */
  public function getAllowedValuesTopLevel() {
    $data = $this->getApiData();
    $options = [];

    foreach ($data as $key => $value) {
      if (isset($value->name)) {
        $options[$key] = $value->name;
      }
    }

    uasort($options, [$this, 'orderOptions']);

    return $options;
  }

  /**
   * Get allowed values.
   */
  public function getAllowedValues() {
    $data = $this->getFlatCache();
    $options = [];

    foreach ($data as $key => $level) {
      if (isset($level->name)) {
        $options[$key] = $level->name;
      }
    }

    return $options;
  }

  /**
   * Get children as options.
   */
  public function getChildrenAsOptions($location) {
    $options = [];
    foreach ($location->children as $child) {
      $options[$child->id] = $child->name;
    }

    uasort($options, [$this, 'orderOptions']);

    return $options;
  }

  /**
   * Get item.
   */
  public function getItem($id) {
    $data = $this->getFlatCache();

    if (isset($data[$id])) {
      return $data[$id];
    }

    return FALSE;
  }

}

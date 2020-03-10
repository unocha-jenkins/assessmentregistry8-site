<?php

namespace Drupal\ocha_locations\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Class OchaLocationsController.
 */
class OchaLocationsController extends ControllerBase {

  /**
   * Directory to read/write json files.
   *
   * @var string
   */
  protected $directory = 'public://json';

  /**
   * Guzzle client.
   *
   * @var GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $loggerFactory;

  /**
   * The state store.
   *
   * @var Drupal\Core\State\State
   */
  protected $state;

  /**
   * The file system.
   *
   * @var Drupal\Core\File\FileSystem
   */
  protected $file;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $config, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory, State $state, FileSystemInterface $file) {
    $this->httpClient = $httpClient;
    $this->config = $config->get('ocha_locations.settings');
    $this->cacheBackend = $cache;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->file = $file;
  }

  /**
   * Load API data from json.
   */
  public function getApiDataFromJson() {
    if (file_exists($this->directory . '/ocha_locations.json')) {
      $this->loggerFactory->get('ocha_locations')->notice('Loading data from ocha_locations.json');

      $data = file_get_contents($this->directory . '/ocha_locations.json');
      $data = json_decode($data);

      return $this->fillCache($data);
    }
  }

  /**
   * Get API data.
   */
  public function getApiData($reset = FALSE) {
    $cid = 'ocha_locations:apiData';

    // Return cached data.
    if (!$reset && $cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $data = [];

    // Load cached data in case of API failures.
    if ($cache = $this->cacheBackend->get($cid)) {
      $data = $cache->data;
    }

    $api_endpoint = $this->config->get('api.endpoint');
    $admin_levels = [0, 1, 2];

    // Add limit.
    $api_endpoint;

    try {
      foreach ($admin_levels as $admin_level) {
        // Combined data.
        $combined_data = [];

        $ts = $this->state->get('ocha_locations_fetch_and_sync_ts_' . $admin_level);
        $url = $api_endpoint . '?filter[admin_level]=' . $admin_level;
        $url .= '&filter[changed][value]=' . $ts . '&filter[changed][operator]=>';
        $url .= '&sort=changed,id';

        while (TRUE) {
          $this->loggerFactory->get('ocha_locations')->notice('Fetching ocha_locations from @url', [
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
            $this->loggerFactory->get('ocha_locations')->error('Fetching ocha_locations failed with @status', [
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

      // Store file in public://json/ocha_locations.json.
      $this->file->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
      $this->file->saveData(json_encode($data), $this->directory . '/ocha_locations.json', FileSystemInterface::EXISTS_REPLACE);
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_locations')->error('Exception while fetching ocha_locations with @status', [
        '@status' => $exception->getMessage(),
      ]);

      // Return cached data.
      return $data;
    }

    return $data;
  }

  /**
   * Fill cache.
   */
  private function fillCache($data) {
    $cid = 'ocha_locations:apiData';

    // Allow updating the cache.
    $keyed_data = [];
    if ($cache = $this->cacheBackend->get($cid)) {
      $keyed_data = $cache->data;
    }

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

    $this->loggerFactory->get('ocha_locations')->notice('OCHA locations imported, got @num locations', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer locations then last time.
    if (count($keyed_data) < $this->state->get('ocha_locations_count')) {
      $this->loggerFactory->get('ocha_locations')->error('We had @before locations before, now only @after', [
        '@before' => $this->state->get('ocha_locations_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_locations_count', count($keyed_data));

    if (!empty($keyed_data)) {
      // Cache forever.
      $this->cacheBackend->set($cid, $keyed_data, Cache::PERMANENT);

      // Invalidate cache.
      Cache::invalidateTags(['ocha_locations']);
    }

    return $keyed_data;
  }

  /**
   * Append to cache.
   */
  private function appendToCache($data) {
    $cid = 'ocha_locations:apiData';

    if ($cache = $this->cacheBackend->get($cid)) {
      $keyed_data = $cache->data;
    }

    // Key data by id.
    foreach ($data as $row) {
      $parents = [];
      foreach($row->parents as $parent) {
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
        $this->loggerFactory->get('ocha_locations')->notice('Missing parent @num', [
          '@num' => $row->parent[0]->id,
        ]);
      }
    }

    if (!empty($keyed_data)) {
      // Cache forever.
      $this->cacheBackend->set($cid, $keyed_data, Cache::PERMANENT);

      // Invalidate cache.
      Cache::invalidateTags(['ocha_locations']);
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

    uasort($options, function ($a, $b) {
      return strcmp(iconv('utf8', 'ASCII//TRANSLIT', $a), iconv('utf8', 'ASCII//TRANSLIT', $b));
    });

    return $options;
  }

  /**
   * Get item.
   */
  public function getItem($id) {
    $data = $this->getApiData();

    if (isset($data[$id])) {
      return $data[$id];
    }

    return FALSE;
  }

}

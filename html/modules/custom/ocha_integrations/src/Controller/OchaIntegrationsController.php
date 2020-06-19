<?php

namespace Drupal\ocha_integrations\Controller;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\State;
use GuzzleHttp\ClientInterface;

/**
 * Class OchaIntegrationsController.
 */
class OchaIntegrationsController extends ControllerBase {

  /**
   * Settings name.
   *
   * @var string
   */
  protected $settingsName = '';

  /**
   * JSON file name.
   *
   * @var string
   */
  protected $jsonFilename = '';

  /**
   * Cache Id.
   *
   * @var string
   */
  protected $cacheId = '';

  /**
   * Cache Tag.
   *
   * @var string
   */
  protected $cacheTag = '';

  /**
   * Logger Id.
   *
   * @var string
   */
  protected $loggerId = '';

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
  protected static $staticCache;

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $config, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory, State $state, FileSystemInterface $file) {
    $this->httpClient = $httpClient;
    $this->config = $config->get($this->settingsName);
    $this->cacheBackend = $cache;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->file = $file;
  }

  /**
   * Load API data from json.
   */
  public function getApiDataFromJson() {
    if (file_exists($this->directory . '/' . $this->jsonFilename)) {
      $this->loggerFactory->get('ocha_themes')->notice('Loading data from @filename', [
        '@filename' => $this->jsonFilename,
      ]);

      $data = file_get_contents($this->directory . '/' . $this->jsonFilename);
      $data = json_decode($data);

      return $this->populateCache($data);
    }
  }

  /**
   * Store cache as json.
   */
  public function saveToJson($data) {
    $this->file->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
    $this->file->saveData(json_encode($data), $this->directory . '/' . $this->jsonFilename, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Store data in cache.
   */
  public function populateCache($data) {
    // Cache forever.
    $this->cacheBackend->set($this->cacheId, $data, Cache::PERMANENT);

    // Invalidate cache.
    Cache::invalidateTags([$this->cacheTag]);

    // Set static cache.
    static::$staticCache = $data;
  }

  /**
   * Get cached data.
   */
  public function getCache() {
    if (isset(static::$staticCache)) {
      return static::$staticCache;
    }

    if ($cache = $this->cacheBackend->get($this->cacheId)) {
      static::$staticCache = $cache->data;
      return static::$staticCache;
    }

    return [];
  }

  /**
   * Load API data from endpoint.
   */
  public function getApiDataFromEndpoint() {
  }

  /**
   * Get API data.
   */
  public function getApiData($reset = FALSE) {
    // Return cached data.
    if (!$reset && $cache = $this->cacheBackend->get($this->cacheId)) {
      return $cache->data;
    }

    $data = $this->getApiDataFromEndpoint();

    return $data;
  }

  /**
   * Order options alphabetically, ignoring accents.
   */
  public function orderOptions($a, $b) {
    return strcmp(iconv('UTF-8', 'ASCII//TRANSLIT', $a), iconv('UTF-8', 'ASCII//TRANSLIT', $b));
  }

  /**
   * Get item.
   */
  public function getItem($id) {
    $data = $this->getCache();

    if (isset($data[$id])) {
      return $data[$id];
    }

    return FALSE;
  }

}

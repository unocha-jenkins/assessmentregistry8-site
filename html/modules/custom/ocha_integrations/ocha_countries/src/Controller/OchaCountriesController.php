<?php

namespace Drupal\ocha_countries\Controller;

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
 * Class OchaCountriesController.
 */
class OchaCountriesController extends ControllerBase {

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
    $this->config = $config->get('ocha_countries.settings');
    $this->cacheBackend = $cache;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->file = $file;
  }

  /**
   * Load API data from json.
   */
  public function getApiDataFromJson() {
    if (file_exists($this->directory . '/ocha_countries.json')) {
      $this->loggerFactory->get('ocha_countries')->notice('Loading data from ocha_countries.json');

      $data = file_get_contents($this->directory . '/ocha_countries.json');
      $data = json_decode($data);

      return $this->fillCache($data);
    }
  }

  /**
   * Get API data.
   */
  public function getApiData($reset = FALSE) {
    $cid = 'ocha_countries:apiData';

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

    try {
      $this->loggerFactory->get('ocha_countries')->notice('Fetching ocha_countries from @url', [
        '@url' => $api_endpoint,
      ]);

      $response = $this->httpClient->request('GET', $api_endpoint);

      if ($response->getStatusCode() === 200) {
        $raw = $response->getBody()->getContents();
        $data = json_decode($raw);

        $data = $this->fillCache($data);

        // Store file in public://json/ocha_countries.json.
        $this->file->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
        $this->file->saveData($raw, $this->directory . '/ocha_countries.json', FileSystemInterface::EXISTS_REPLACE);
      }
      else {
        $this->loggerFactory->get('ocha_countries')->error('Fetching ocha_countries failed with @status', [
          '@status' => $response->getStatusCode(),
        ]);

        // Return cached data.
        return $data;
      }
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_countries')->error('Exception while fetching ocha_countries with @status', [
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
    $cid = 'ocha_countries:apiData';

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
      // Cache forever.
      $this->cacheBackend->set($cid, $keyed_data, Cache::PERMANENT);

      // Invalidate cache.
      Cache::invalidateTags(['ocha_countries']);
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

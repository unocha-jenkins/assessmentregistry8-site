<?php

namespace Drupal\ocha_local_groups\Controller;

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
 * Class OchaLocalGroupsController.
 */
class OchaLocalGroupsController extends ControllerBase {

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
    $this->config = $config->get('ocha_local_groups.settings');
    $this->cacheBackend = $cache;
    $this->loggerFactory = $logger_factory;
    $this->state = $state;
    $this->file = $file;
  }

  /**
   * Load API data from json.
   */
  public function getApiDataFromJson() {
    if (file_exists($this->directory . '/ocha_local_groups.json')) {
      $this->loggerFactory->get('ocha_local_groups')->notice('Loading data from ocha_local_groups.json');

      $data = file_get_contents($this->directory . '/ocha_local_groups.json');
      $data = json_decode($data);

      return $this->fillCache($data);
    }
  }

  /**
   * Get API data.
   */
  public function getApiData($reset = FALSE) {
    $cid = 'ocha_local_groups:apiData';

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
    $url = $api_endpoint;

    // Combined data.
    $combined_data = [];

    try {
      while (TRUE) {
        $this->loggerFactory->get('ocha_local_groups')->notice('Fetching ocha_local_groups from @url', [
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
          $this->loggerFactory->get('ocha_local_groups')->error('Fetching ocha_local_groups failed with @status', [
            '@status' => $response->getStatusCode(),
          ]);

          // Return cached data.
          return $data;
        }
      }

      $data = $this->fillCache($combined_data);

      // Store file in public://json/ocha_local_groups.json.
      $this->file->prepareDirectory($this->directory, FileSystemInterface::CREATE_DIRECTORY);
      $this->file->saveData(json_encode($data), $this->directory . '/ocha_local_groups.json', FileSystemInterface::EXISTS_REPLACE);
    }
    catch (RequestException $exception) {
      $this->loggerFactory->get('ocha_local_groups')->error('Exception while fetching ocha_local_groups with @status', [
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
    $cid = 'ocha_local_groups:apiData';

    // Key data by id.
    $keyed_data = [];
    foreach ($data as $row) {
      $keyed_data[$row->id] = (object) [
        'name' => trim($row->label),
        'href' => $row->self,
        'global_cluster' => $row->global_cluster,
        'lead_agencies' => $row->lead_agencies,
        'partners' => $row->partners,
        'activation_document' => $row->activation_document,
        'operations' => $row->operation,
      ];
    }

    $this->loggerFactory->get('ocha_local_groups')->notice('OCHA local groups imported, got @num local groups', [
      '@num' => count($keyed_data),
    ]);

    // Check if we have fewer local groups then last time.
    if (count($keyed_data) < $this->state->get('ocha_local_groups_count')) {
      $this->loggerFactory->get('ocha_local_groups')->error('We had @before local groups before, now only @after', [
        '@before' => $this->state->get('ocha_local_groups_count'),
        '@after' => count($keyed_data),
      ]);
    }
    $this->state->set('ocha_local_groups_count', count($keyed_data));

    if (!empty($keyed_data)) {
      // Cache forever.
      $this->cacheBackend->set($cid, $keyed_data, Cache::PERMANENT);

      // Invalidate cache.
      Cache::invalidateTags(['ocha_local_groups']);
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

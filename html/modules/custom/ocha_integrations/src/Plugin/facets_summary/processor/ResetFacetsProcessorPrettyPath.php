<?php

namespace Drupal\ocha_integrations\Plugin\facets_summary\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Plugin\facets_summary\processor\ResetFacetsProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a processor that adds a link to reset facet filters.
 *
 * @SummaryProcessor(
 *   id = "reset_facets_pretty_path",
 *   label = @Translation("Adds reset facets link (OCHA)."),
 *   description = @Translation("When checked, this facet will add a link to reset enabled facets."),
 *   stages = {
 *     "build" = 30
 *   }
 * )
 */
class ResetFacetsProcessorPrettyPath extends ResetFacetsProcessor implements ContainerFactoryPluginInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Builds ResetFacetsProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin_definition for the plugin instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RequestStack $request_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('request_stack'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(FacetsSummaryInterface $facets_summary, array $build, array $facets) {
    $configuration = $facets_summary->getProcessorConfigs()[$this->getPluginId()];
    $hasReset = FALSE;

    // Do nothing if there are no selected facets or reset text is empty.
    if (empty($build['#items']) || empty($configuration['settings']['link_text'])) {
      return $build;
    }

    $request = $this->requestStack->getMasterRequest();
    $query_params = $request->query->all();

    if (isset($configuration['settings']['clear_string']) && $configuration['settings']['clear_string']) {
      if (!empty($query_params['s'])) {
        unset($query_params['s']);
        $hasReset = TRUE;
      }
    }

    // Check if we have an active filter.
    foreach ($facets as $facet) {
      if ($facet->getActiveItems()) {
        $hasReset = TRUE;
      }
    }

    if (!$hasReset) {
      return $build;
    }

    $url = Url::fromUserInput($facets_summary->getFacetSource()->getPath());
    $url->setOptions(['query' => $query_params]);

    $item = (new Link($configuration['settings']['link_text'], $url))->toRenderable();
    $item['#wrapper_attributes'] = [
      'class' => [
        'facet-summary-item--clear',
      ],
    ];

    // Add to the end.
    array_push($build['#items'], $item);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, FacetsSummaryInterface $facets_summary) {
    // By default, there should be no config form.
    $config = $this->getConfiguration();

    $build['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reset facets link text'),
      '#default_value' => $config['link_text'],
    ];

    $build['clear_string'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear the current search string'),
      '#default_value' => $config['clear_string'],
      '#description' => $this->t('If checked, the reset link will also clear the text used for the search.'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'link_text' => '',
      'clear_string' => TRUE,
    ];
  }

}

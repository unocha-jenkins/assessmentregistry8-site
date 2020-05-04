<?php

namespace Drupal\ocha_integrations\Plugin\facets_summary\processor;

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\facets_summary\FacetsSummaryInterface;
use Drupal\facets_summary\Processor\BuildProcessorInterface;
use Drupal\facets_summary\Processor\ProcessorPluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a processor that adds a link to reset facet filters.
 *
 * @SummaryProcessor(
 *   id = "reset_string_ocha",
 *   label = @Translation("Adds reset search string link (OCHA)"),
 *   description = @Translation("When checked, this will add a link to reset the search string."),
 *   stages = {
 *     "build" = 30
 *   }
 * )
 */
class ResetStringProcessorOcha extends ProcessorPluginBase implements BuildProcessorInterface, ContainerFactoryPluginInterface {

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
    $request = $this->requestStack->getMasterRequest();
    $query_params = $request->query->all();

    $current_string = $request->query->get('s');
    if (empty($current_string)) {
      return $build;
    }

    unset($query_params['s']);

    $url = Url::createFromRequest($request);
    $url->setOptions(['query' => $query_params]);

    $markup = Markup::create($current_string);
    $item = (new Link($markup, $url))->toRenderable();

    $build_string = [
      '#theme' => 'ocha_integrations_facets_reset_string',
      '#search_string' => $item,
      '#wrapper_attributes' => [
        'class' => [
          'facet-summary-item--search-string',
        ],
      ],
      '#cache' => [
        'contexts' => [
          'url.query_args:s',
        ],
      ],
    ];

    array_push($build['#items'], $build_string);

    return $build;
  }

}

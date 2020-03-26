<?php

namespace Drupal\ocha_map\Plugin\views\style;

use Drupal\leaflet_views\Plugin\views\style\LeafletMap;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\leaflet_views\Controller\LeafletAjaxPopupController;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\leaflet\LeafletSettingsElementsTrait;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Style plugin to render a View output as a Leaflet map.
 *
 * @ingroup views_style_plugins
 *
 * Attributes set below end up in the $this->definition[] array.
 *
 * @ViewsStyle(
 *   id = "ocha_leaflet_map",
 *   title = @Translation("OCHA Leaflet Map"),
 *   help = @Translation("Displays a View as a Leaflet map."),
 *   display_types = {"normal"},
 *   theme = "leaflet-map"
 * )
 */
class OchaLeafletMap extends LeafletMap implements ContainerFactoryPluginInterface {

  use LeafletSettingsElementsTrait;

  /**
   * The Default Settings.
   *
   * @var array
   */
  protected $defaultSettings;

  /**
   * The Entity source property.
   *
   * @var string
   */
  protected $entitySource;

  /**
   * The Entity type property.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The Entity Info service property.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityInfo;

  /**
   * Does the style plugin for itself support to add fields to it's output.
   *
   * @var bool
   */
  protected $usesFields = TRUE;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The Entity Field manager service property.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The Entity Display Repository service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplay;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * The Renderer service property.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $renderer;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Leaflet service.
   *
   * @var \Drupal\Leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The list of fields added to the view.
   *
   * @var array
   */
  protected $viewFields = [];

  /**
   * Field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_display.repository'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('renderer'),
      $container->get('module_handler'),
      $container->get('leaflet.service'),
      $container->get('link_generator'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * Get a list of fields and a sublist of geo data fields in this view.
   *
   * @return array
   *   Available data sources.
   */
  protected function getAvailableDataSources() {
    $fields_geo_data = [];

    /* @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler) */
    foreach ($this->displayHandler->getHandlers('field') as $field_id => $handler) {
      $label = $handler->adminLabel() ?: $field_id;
      $this->viewFields[$field_id] = $label;

      $fields_geo_data[$field_id] = $label;
    }

    return $fields_geo_data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Map preset.
    $form['data_source']['#description'] = $this->t('Field should contain a lat, lon pair string');
  }

  /**
   * Renders the View.
   */
  public function render() {
    // Performs some preprocess on the leaflet map settings.
    $this->leafletService->preProcessMapSettings($this->options);

    $data = [];

    // Collect bubbleable metadata when doing early rendering.
    $build_for_bubbleable_metadata = [];

    // Always render the map, otherwise ...
    $leaflet_map_style = !isset($this->options['leaflet_map']) ? $this->options['map'] : $this->options['leaflet_map'];
    $map = leaflet_map_get_info($leaflet_map_style);

    // Set Map additional map Settings.
    $this->setAdditionalMapOptions($map, $this->options);

    // Add a specific map id.
    $map['id'] = Html::getUniqueId("leaflet_map_view_" . $this->view->id() . '_' . $this->view->current_display);

    if ($geofield_name = $this->options['data_source']) {
      $this->renderFields($this->view->result);

      /* @var \Drupal\views\ResultRow $result */
      foreach ($this->view->result as $result) {

        // For proper processing make sure the geofield_value is created as
        // an array, also if single value.
        $geofield_value = (array) $this->getFieldValue($result->index, $geofield_name);

        if (!empty($geofield_value)) {
          $geom = explode(',', $geofield_value[0]);
          $features = [
            [
              'type' => 'point',
              'lat' => $geom[1],
              'lon' => $geom[0],
            ],
          ];

          if (!empty($result->_entity)) {
            // Entity API provides a plain entity object.
            $entity = $result->_entity;
          }
          elseif (isset($result->_object)) {
            // Search API provides a TypedData EntityAdapter.
            $entity_adapter = $result->_object;
            if ($entity_adapter instanceof EntityAdapter) {
              $entity = $entity_adapter->getValue();
            }
          }

          // Render the entity with the selected view mode.
          if (isset($entity)) {
            // Get and set (if not set) the Geofield cardinality.
            /* @var \Drupal\Core\Field\FieldItemList $geofield_entity */
            if (!isset($map['geofield_cardinality'])) {
              try {
                $geofield_entity = $entity->get($geofield_name);
                $map['geofield_cardinality'] = $geofield_entity->getFieldDefinition()
                  ->getFieldStorageDefinition()
                  ->getCardinality();
              }
              catch (\Exception $e) {
                // In case of exception it means that $geofield_name field is
                // not directly related to the $entity and might be the case of
                // a geofield exposed through a relationship.
                // In this case it is too complicate to get the geofield related
                // entity, so apply a more general case of multiple/infinite
                // geofield_cardinality.
                // @see: https://www.drupal.org/project/leaflet/issues/3048089
                $map['geofield_cardinality'] = -1;
              }
            }

            $entity_type = $entity->getEntityTypeId();
            $entity_type_langcode_attribute = $entity_type . '_field_data_langcode';

            $view = $this->view;

            // Set the langcode to be used for rendering the entity.
            $rendering_language = $view->display_handler->getOption('rendering_language');
            $dynamic_renderers = [
              '***LANGUAGE_entity_translation***' => 'TranslationLanguageRenderer',
              '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
            ];
            if (isset($dynamic_renderers[$rendering_language])) {
              /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
              $langcode = isset($result->$entity_type_langcode_attribute) ? $result->$entity_type_langcode_attribute : $entity->language()
                ->getId();
            }
            else {
              if (strpos($rendering_language, '***LANGUAGE_') !== FALSE) {
                $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
              }
              else {
                // Specific langcode set.
                $langcode = $rendering_language;
              }
            }

            switch ($this->options['description_field']) {
              case '#rendered_entity':
                $build = $this->entityManager->getViewBuilder($entity->getEntityTypeId())
                  ->view($entity, $this->options['view_mode'], $langcode);
                $render_context = new RenderContext();
                $description = $this->renderer->executeInRenderContext($render_context, function () use (&$build) {
                  return $this->renderer->render($build, TRUE);
                });
                if (!$render_context->isEmpty()) {
                  $render_context->update($build_for_bubbleable_metadata);
                }
                break;

              case '#rendered_entity_ajax':
                $parameters = [
                  'entity_type' => $entity_type,
                  'entity' => $entity->id(),
                  'view_mode' => $this->options['view_mode'],
                  'langcode' => $langcode,
                ];
                $url = Url::fromRoute('leaflet_views.ajax_popup', $parameters, ['absolute' => TRUE]);
                $description = sprintf('<div class="leaflet-ajax-popup" data-leaflet-ajax-popup="%s" %s></div>',
                  $url->toString(), LeafletAjaxPopupController::getPopupIdentifierAttribute($entity_type, $entity->id(), $this->options['view_mode'], $langcode));
                $map['settings']['ajaxPoup'] = TRUE;
                break;

              default:
                // Normal rendering via fields.
                $description = !empty($this->options['description_field']) ? $this->rendered_fields[$result->index][$this->options['description_field']] : '';
            }

            // Relates the feature with its entity id, so that it might be
            // referenced from outside.
            foreach ($features as &$feature) {
              $feature['entity_id'] = $entity->id();
            }

            // Attach pop-ups if we have a description field.
            if (isset($description)) {
              foreach ($features as &$feature) {
                $feature['popup'] = $description;
              }
            }

            // Attach also titles, they might be used later on.
            if ($this->options['name_field']) {
              foreach ($features as &$feature) {
                // Decode any entities because JS will encode them again and
                // we don't want double encoding.
                $feature['label'] = !empty($this->options['name_field']) ? Html::decodeEntities(($this->rendered_fields[$result->index][$this->options['name_field']])) : '';
              }
            }

            // Merge eventual map icon definition from hook_leaflet_map_info.
            if (!empty($map['icon'])) {
              $this->options['icon'] = $this->options['icon'] ?: [];
              // Remove empty icon options so that they might be replaced by
              // the ones set by the hook_leaflet_map_info.
              foreach ($this->options['icon'] as $k => $icon_option) {
                if (empty($icon_option) || (is_array($icon_option) && $this->leafletService->multipleEmpty($icon_option))) {
                  unset($this->options['icon'][$k]);
                }
              }
              $this->options['icon'] = array_replace($map['icon'], $this->options['icon']);
            }

            // Define possible tokens.
            $tokens = [];
            foreach ($this->rendered_fields[$result->index] as $field_name => $field_value) {
              $tokens[$field_name] = $field_value;
              $tokens["{{ $field_name }}"] = $field_value;
            }

            $icon_type = isset($this->options['icon']['iconType']) ? $this->options['icon']['iconType'] : 'marker';

            // Eventually set the custom icon as DivIcon or Icon Url.
            if ($icon_type === 'marker' && !empty($this->options['icon']['iconUrl'])
              || $icon_type === 'html' && !empty($this->options['icon']['html'])) {
              foreach ($features as &$feature) {
                if ($feature['type'] === 'point' && $icon_type === 'html' && !empty($this->options['icon']['html'])) {
                  $feature['icon'] = $this->options['icon'];
                  $feature['icon']['html'] = $this->viewsTokenReplace($this->options['icon']['html'], $tokens);
                  $feature['icon']['html_class'] = $this->options['icon']['html_class'];
                }
                elseif ($feature['type'] === 'point' && !empty($this->options['icon']['iconUrl'])) {
                  $feature['icon'] = $this->options['icon'];
                  $feature['icon']['iconUrl'] = $this->viewsTokenReplace($this->options['icon']['iconUrl'], $tokens);
                  if (!empty($this->options['icon']['shadowUrl'])) {
                    $feature['icon']['shadowUrl'] = $this->viewsTokenReplace($this->options['icon']['shadowUrl'], $tokens);
                  }
                }
              }
            }

            // Associate dynamic path properties (token based) to each feature,
            // in case of not point.
            foreach ($features as &$feature) {
              if ($feature['type'] !== 'point') {
                $feature['path'] = str_replace(["\n", "\r"], "", $this->viewsTokenReplace($this->options['path'], $tokens));
              }
            }

            foreach ($features as &$feature) {
              // Allow modules to adjust the marker.
              \Drupal::moduleHandler()->alter('leaflet_views_feature', $feature, $result, $this->view->rowPlugin);
            }
            // Add new points to the whole basket.
            $data = array_merge($data, $features);
          }
        }
      }
    }

    // Don't render the map, if we do not have any data
    // and the hide option is set.
    if (empty($data) && !empty($this->options['hide_empty_map'])) {
      return [];
    }

    $js_settings = [
      'map' => $map,
      'features' => $data,
    ];

    // Allow other modules to add/alter the map js settings.
    $this->moduleHandler->alter('leaflet_map_view_style', $js_settings, $this);

    $map_height = !empty($this->options['height']) ? $this->options['height'] . $this->options['height_unit'] : '';
    $element = $this->leafletService->leafletRenderMap($js_settings['map'], $js_settings['features'], $map_height);
    // Add the Core Drupal Ajax library for Ajax Popups.
    if (isset($map['settings']['ajaxPoup']) && $map['settings']['ajaxPoup'] == TRUE) {
      $build_for_bubbleable_metadata['#attached']['library'][] = 'core/drupal.ajax';
    }
    BubbleableMetadata::createFromRenderArray($element)
      ->merge(BubbleableMetadata::createFromRenderArray($build_for_bubbleable_metadata))
      ->applyTo($element);
    return $element;
  }

}

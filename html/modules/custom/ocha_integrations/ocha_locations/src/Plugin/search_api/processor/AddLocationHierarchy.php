<?php

namespace Drupal\ocha_locations\Plugin\search_api\processor;

use Drupal\search_api\Plugin\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\Plugin\search_api\processor\AddHierarchy;

/**
 * Adds all ancestors' IDs to a hierarchical field.
 *
 * @SearchApiProcessor(
 *   id = "location_hierarchy",
 *   label = @Translation("Index location hierarchy"),
 *   description = @Translation("Allows the indexing of values along with all their ancestors for hierarchical fields (like taxonomy term references)"),
 *   stages = {
 *     "preprocess_index" = -45
 *   }
 * )
 */
class AddLocationHierarchy extends AddHierarchy {

  use PluginFormTrait;

  /**
   * Static cache for getHierarchyFields() return values, keyed by index ID.
   *
   * @var string[][][]
   *
   * @see \Drupal\search_api\Plugin\search_api\processor\AddHierarchy::getHierarchyFields()
   */
  protected static $indexHierarchyFields = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setEntityTypeManager($container->get('entity_type.manager'));

    return $processor;
  }

  /**
   * Finds all (potentially) hierarchical fields for this processor's index.
   *
   * Fields are returned if:
   * - they point to an entity type; and
   * - that entity type contains a property referencing the same type of entity
   *   (so that a hierarchy could be built from that nested property).
   *
   * @return string[][]
   *   An array containing all fields of the index for which hierarchical data
   *   might be retrievable. The keys are those field's IDs, the values are
   *   associative arrays containing the nested properties of those fields from
   *   which a hierarchy might be constructed, with the property paths as the
   *   keys and labels as the values.
   */
  protected function getHierarchyFields() {
    if (!isset(static::$indexHierarchyFields[$this->index->id()])) {
      $field_options = [];

      foreach ($this->index->getFields() as $field_id => $field) {
        $definition = $field->getDataDefinition();
        if ($definition->getDataType() == 'field_item:ocha_locations') {
          $field_options[$field_id] = [
            'ocha_locations-hierarchy' => 'OCHA locations » Hierarchy',
          ];
        }
      }

      static::$indexHierarchyFields[$this->index->id()] = $field_options;
    }

    return static::$indexHierarchyFields[$this->index->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($this->configuration['fields'] as $field_id => $property_specifier) {
        // Fetch correct field.
        $field = $item->getField($field_id);
        $field_values = $field->getValues();

        if (!$field) {
          continue;
        }

        foreach ($field_values as $id) {
          // Get all parent ids.
          $location = ocha_locations_get_item($id);
          if ($location) {
            do {
              if (!in_array($location->id, $field->getValues())) {
                $field->addValue($location->id);
              }

              if (!empty($location->parent)) {
                $location = ocha_locations_get_item($location->parent);
              }
              else {
                $location = FALSE;
              }
            } while ($location);
          }
        }
      }
    }
  }

}

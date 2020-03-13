<?php

namespace Drupal\ocha_locations\Plugin\search_api\processor;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Plugin\search_api\data_type\value\TextValue;
use Drupal\search_api\Utility\Utility;
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
   * Retrieves the entity type manager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager service.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $processor = new static(['#index' => $index], 'hierarchy', []);
    return (bool) $processor->getHierarchyFields();
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
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form['#description'] = $this->t('Select the fields to which hierarchical data should be added.');

    foreach ($this->getHierarchyFields() as $field_id => $options) {
      $enabled = !empty($this->configuration['fields'][$field_id]);
      $form['fields'][$field_id]['status'] = [
        '#type' => 'checkbox',
        '#title' => $this->index->getField($field_id)->getLabel(),
        '#default_value' => $enabled,
      ];
      reset($options);
      $form['fields'][$field_id]['property'] = [
        '#type' => 'radios',
        '#title' => $this->t('Hierarchy property to use'),
        '#description' => $this->t("This field has several nested properties which look like they might contain hierarchy data for the field. Please pick the one that should be used."),
        '#options' => $options,
        '#default_value' => $enabled ? $this->configuration['fields'][$field_id] : key($options),
        '#access' => count($options) > 1,
        '#states' => [
          'visible' => [
            // @todo This shouldn't be dependent on the form array structure.
            //   Use the '#process' trick instead.
            ":input[name=\"processors[hierarchy][settings][fields][$field_id][status]\"]" => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $formState) {
    $fields = [];
    foreach ($formState->getValue('fields', []) as $field_id => $values) {
      if (!empty($values['status'])) {
        if (empty($values['property'])) {
          $formState->setError($form['fields'][$field_id]['property'], $this->t('You need to select a nested property to use for the hierarchy data.'));
        }
        else {
          $fields[$field_id] = $values['property'];
        }
      }
    }
    $formState->setValue('fields', $fields);
    if (!$fields) {
      $formState->setError($form['fields'], $this->t('You need to select at least one field for which to add hierarchy data.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      foreach ($this->configuration['fields'] as $field_id => $property_specifier) {
        // Convert to a processor that adds the field.
        $field = $item->getField($field_id . '_hierarchy');
        $field_values = $field->getValues();
        \Drupal::logger('my_module')->notice(print_r($field_values, TRUE));
        // Fetch correct field.
        $field = $item->getField($field_id);
        $field_values = $field->getValues();
        \Drupal::logger('my_module2')->notice(print_r($field_values, TRUE));

        if (!$field) {
          continue;
        }

        foreach ($field_values as $idstring) {
          $ids = explode('|', $idstring);
          foreach ($ids as $id) {
            if (!in_array($id, $field_values)) {
              $field->addValue($id);
            }
          }
        }
      }
    }
  }

}

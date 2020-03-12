<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ocha_locations' field type.
 *
 * @FieldType (
 *   id = "ocha_locations",
 *   label = @Translation("OCHA locations"),
 *   description = @Translation("List of OCHA locations."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_locations_select",
 *   default_formatter = "ocha_locations_default"
 * )
 */
class OchaLocations extends FieldItemBase {

    /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_locations_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'level0';
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'level0' => [
          'type' => 'int',
        ],
        'level1' => [
          'type' => 'int',
        ],
        'level2' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $level0 = $this->get('level0')->getValue();
    return empty($level0);
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['level0'] = DataDefinition::create('integer')
      ->setLabel(t('Level 0'))
      ->setRequired(FALSE)
      ->setDescription(t('Level 0'));

    $properties['level1'] = DataDefinition::create('integer')
      ->setLabel(t('Level 1'))
      ->setRequired(FALSE)
      ->setDescription(t('Level 1'));

    $properties['level2'] = DataDefinition::create('integer')
      ->setLabel(t('Level 2'))
      ->setRequired(FALSE)
      ->setDescription(t('Level 2'));

    $properties['hierarchy'] = DataDefinition::create('string')
      ->setLabel(t('Hierarchy'))
      ->setDescription(t('Hierarchy of the levels'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_locations\OchaLocationsHierachy');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    parent::setValue($values);
    $this->populateComputedValues();
  }

   /**
   * Populates computed variables.
   */
  protected function populateComputedValues() {
      $this->hierarchy = $this->level0;
      if (!empty($this->level1)) {
        $this->hierarchy .= '|' . $this->level1;
      }
      if (!empty($this->level2)) {
        $this->hierarchy .= '|' . $this->level2;
      }
  }

}
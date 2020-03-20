<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

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
class OchaLocations extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['lat_lon'] = DataDefinition::create('string')
      ->setLabel(t('Lat, lon pair'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_locations\GetLocationLatLon');

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_locations\GetLabel');

    return $properties;
  }

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
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $allowed_options = ocha_locations_allowed_values();
    $values['value'] = array_rand($allowed_options);
    return $values;
  }

}

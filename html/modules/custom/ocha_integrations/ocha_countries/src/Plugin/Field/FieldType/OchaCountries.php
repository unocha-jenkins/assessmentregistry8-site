<?php

namespace Drupal\ocha_countries\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_countries' field type.
 *
 * @FieldType (
 *   id = "ocha_countries",
 *   label = @Translation("OCHA countries"),
 *   description = @Translation("List of OCHA countries."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_countries_default"
 * )
 */
class OchaCountries extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_countries\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_countries_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

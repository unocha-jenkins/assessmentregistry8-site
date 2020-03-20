<?php

namespace Drupal\ocha_themes\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_themes' field type.
 *
 * @FieldType (
 *   id = "ocha_themes",
 *   label = @Translation("OCHA themes"),
 *   description = @Translation("List of OCHA themes."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_integrations_default"
 * )
 */
class OchaThemes extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_themes\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_themes_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

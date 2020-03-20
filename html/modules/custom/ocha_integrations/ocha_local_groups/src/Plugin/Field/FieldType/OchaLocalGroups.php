<?php

namespace Drupal\ocha_local_groups\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_local_groups' field type.
 *
 * @FieldType (
 *   id = "ocha_local_groups",
 *   label = @Translation("OCHA local groups"),
 *   description = @Translation("List of OCHA local groups."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_local_groups_default"
 * )
 */
class OchaLocalGroups extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_local_groups\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_local_groups_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

<?php

namespace Drupal\ocha_global_coordination_groups\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_global_coordination_groups' field type.
 *
 * @FieldType (
 *   id = "ocha_global_coordination_groups",
 *   label = @Translation("OCHA global coordination groups"),
 *   description = @Translation("List of OCHA global coordination groups."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_global_coordination_groups_default"
 * )
 */
class OchaGlobalCoordinationGroups extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_global_coordination_groups\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_global_coordination_groups_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

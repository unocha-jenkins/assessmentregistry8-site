<?php

namespace Drupal\ocha_organizations\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_organizations' field type.
 *
 * @FieldType (
 *   id = "ocha_organizations",
 *   label = @Translation("OCHA organizations"),
 *   description = @Translation("List of OCHA organizations."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_integrations_default"
 * )
 */
class OchaOrganizations extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_organizations\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_organizations_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

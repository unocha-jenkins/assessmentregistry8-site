<?php

namespace Drupal\ocha_hid_contacts\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'ocha_hid_contacts' field type.
 *
 * @FieldType (
 *   id = "ocha_hid_contacts",
 *   label = @Translation("Ocha HID Contacts"),
 *   description = @Translation("List of Ocha HID Contacts."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_hid_contacts_autocomplete",
 *   default_formatter = "ocha_hid_contacts_default"
 * )
 */
class OchaHidContacts extends ListStringItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['label'] = DataDefinition::create('string')
      ->setLabel(t('Label'))
      ->setSetting('case_sensitive', FALSE)
      ->setComputed(TRUE)
      ->setClass('\Drupal\ocha_hid_contacts\GetLabel');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_hid_contacts_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

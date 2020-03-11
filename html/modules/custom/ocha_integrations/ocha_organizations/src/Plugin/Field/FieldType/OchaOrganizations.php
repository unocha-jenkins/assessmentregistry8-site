<?php

namespace Drupal\ocha_organizations\Plugin\Field\FieldType;

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
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_organizations_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

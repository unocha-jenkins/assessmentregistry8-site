<?php

namespace Drupal\ocha_population_type\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_population_type' field type.
 *
 * @FieldType (
 *   id = "ocha_population_type",
 *   label = @Translation("OCHA population type"),
 *   description = @Translation("List of OCHA population type."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_integrations_select",
 *   default_formatter = "ocha_integrations_default"
 * )
 */
class OchaPopulationType extends ListIntegerItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_population_type_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

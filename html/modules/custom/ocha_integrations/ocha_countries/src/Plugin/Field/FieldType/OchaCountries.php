<?php

/**
 * @file
 * Contains \Drupal\ocha_countries\Plugin\Field\FieldType\OchaCountries.
 */

namespace Drupal\ocha_countries\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_countries' field type.
 *
 * @FieldType (
 *   id = "ocha_countries",
 *   label = @Translation("OCHA countries"),
 *   description = @Translation("List of OCHA countries."),
 *   category = @Translation("OCHA"),
 *   default_widget = "options_select",
 *   default_formatter = "ocha_countries_default"
 * )
 */
class OchaCountries extends ListIntegerItem {
  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_countries_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }


}

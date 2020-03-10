<?php

/**
 * @file
 * Contains \Drupal\ocha_disasters\Plugin\Field\FieldType\OchaDisasters.
 */

namespace Drupal\ocha_disasters\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListIntegerItem;

/**
 * Plugin implementation of the 'ocha_disasters' field type.
 *
 * @FieldType (
 *   id = "ocha_disasters",
 *   label = @Translation("OCHA disasters"),
 *   description = @Translation("List of OCHA disasters."),
 *   category = @Translation("OCHA"),
 *   default_widget = "options_select",
 *   default_formatter = "ocha_disasters_default"
 * )
 */
class OchaDisasters extends ListIntegerItem {
  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_disasters_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }


}

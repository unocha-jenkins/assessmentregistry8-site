<?php

namespace Drupal\ocha_themes\Plugin\Field\FieldType;

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
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_themes_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

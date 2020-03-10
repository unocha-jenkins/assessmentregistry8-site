<?php

namespace Drupal\ocha_local_groups\Plugin\Field\FieldType;

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
  public static function defaultStorageSettings() {
    return [
      'allowed_values_function' => 'ocha_local_groups_allowed_values_function',
    ] + parent::defaultStorageSettings();
  }

}

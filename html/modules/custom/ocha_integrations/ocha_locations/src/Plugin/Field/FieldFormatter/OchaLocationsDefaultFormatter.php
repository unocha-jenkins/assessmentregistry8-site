<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'ocha_locations' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_locations_default",
 *   label = @Translation("OCHA locations formatter"),
 *   field_types = {
 *     "ocha_locations"
 *   }
 * )
 */
class OchaLocationsDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($items->count()) {
      foreach ($items as $delta => $item) {
        $parents = [];
        $location_value = $item->value;

        if (!empty($location_value)) {
          $location = ocha_locations_get_item($location_value);
          do {
            $parents[] = $location->name;

            if (!empty($location->parent)) {
              $location = ocha_locations_get_item($location->parent);
            }
            else {
              $location = FALSE;
            }
          } while ($location);
        }

        $parents = array_reverse($parents);
        $output = implode(' - ', $parents);

        $elements[$delta] = [
          '#markup' => $output,
        ];
      }
    }

    return $elements;
  }

}

<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

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
  public static function defaultSettings() {
    return [
      'output' => 'label',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['output'] = [
      '#title' => $this->t('Output'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Label'),
        'glide' => $this->t('Glide'),
      ],
      '#default_value' => $this->getSetting('output'),
    ];

    return $form;
  }

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
            $location = ocha_locations_get_item($location->parent);
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

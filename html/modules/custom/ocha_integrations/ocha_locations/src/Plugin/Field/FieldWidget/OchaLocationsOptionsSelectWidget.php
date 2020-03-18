<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'ocha_locations' widget.
 *
 * @FieldWidget (
 *   id = "ocha_locations_select",
 *   label = @Translation("OCHA select widget"),
 *   field_types = {
 *     "ocha_locations"
 *   }
 * )
 */
class OchaLocationsOptionsSelectWidget extends OptionsSelectWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $location_value = $items[$delta]->value;
    $parents = [];

    // Get new value from AJAX.
    if ($form_state->get('level')) {
      if ($form_state->get('delta') == $delta) {
        $location_value = $form_state->get('value');
      }
    }

    if (!empty($location_value)) {
      $location = ocha_locations_get_item($location_value);
      do {
        $parents[] = $location;

        if (!empty($location->parent)) {
          $location = ocha_locations_get_item($location->parent);
        }
        else {
          $location = FALSE;
        }
      } while ($location);
    }

    // Reverse array so we render parents first.
    $parents = array_reverse($parents);

    $level_counter = 0;
    foreach ($parents as $location) {
      $element['level' . $level_counter] = [
        '#type' => 'select',
        '#title' => $this->t('Level @level', ['@level' => $level_counter]),
        '#default_value' => $location->id,
        '#empty_option' => $this->t('- Select admin level @level -', ['@level' => $level_counter]),
        '#delta' => $delta,
        '#level' => 'l' . $level_counter,
        '#element_validate' => [
          [$this, 'validate'],
        ],
        '#ajax' => [
          'callback' => [$this, 'changeLevel'],
          'event' => 'change',
          'wrapper' => 'ocha-location-wrapper-' . $delta,
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];

      // Set options.
      if ($level_counter == 0) {
        $element['level' . $level_counter]['#options'] = ocha_locations_allowed_values_top_level();
      }
      else {
        $element['level' . $level_counter]['#options'] = ocha_locations_children_to_options($parents[$level_counter - 1]);
      }

      $level_counter++;
    }

    $element['level_counter'] = [
      '#type' => 'value',
      '#value' => $level_counter,
    ];

    // Add next level if needed.
    if ($level_counter > 0) {
      $location = end($parents);
      if (!empty($location->children)) {
        $element['level' . $level_counter] = [
          '#type' => 'select',
          '#options' => ocha_locations_children_to_options($location),
          '#title' => $this->t('Level @level', ['@level' => $level_counter]),
          '#default_value' => '',
          '#empty_option' => $this->t('- Select admin level @level -', ['@level' => $level_counter]),
          '#delta' => $delta,
          '#level' => 'l' . $level_counter,
          '#element_validate' => [
            [$this, 'validate'],
          ],
          '#ajax' => [
            'callback' => [$this, 'changeLevel'],
            'event' => 'change',
            'wrapper' => 'ocha-location-wrapper-' . $delta,
            'progress' => [
              'type' => 'throbber',
            ],
          ],
        ];
      }
    }
    else {
      $element['level' . $level_counter] = [
        '#type' => 'select',
        '#options' => ocha_locations_allowed_values_top_level(),
        '#title' => $this->t('Level @level', ['@level' => $level_counter]),
        '#default_value' => '',
        '#empty_option' => $this->t('- Select admin level @level -', ['@level' => $level_counter]),
        '#delta' => $delta,
        '#level' => 'l' . $level_counter,
        '#element_validate' => [
          [$this, 'validate'],
        ],
        '#ajax' => [
          'callback' => [$this, 'changeLevel'],
          'event' => 'change',
          'wrapper' => 'ocha-location-wrapper-' . $delta,
          'progress' => [
            'type' => 'throbber',
          ],
        ],
      ];
    }

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1) {
      $element += [
        '#type' => 'fieldset',
        '#attributes' => [
          'class' => ['container-inline'],
        ],
      ];
    }

    $element['#prefix'] = '<div id="ocha-location-wrapper-' . $delta . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Validate callback to set form_state values.
   */
  public function validate($element, FormStateInterface $form_state) {
    // Act on triggering element only.
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#level'])) {
      // Only rebuild if needed.
      $form_state->set('level', $triggering_element['#level']);
      $form_state->set('delta', $triggering_element['#delta']);
      $form_state->set('value', $triggering_element['#value']);
      $form_state->setRebuild();
    }
  }

  /**
   * Ajax callback to fill drop downs.
   */
  public function changeLevel(array &$form, FormStateInterface &$form_state) {
    $element = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($element['#array_parents'], 0, -1));

    // Remove weight dropdown.
    if ($element['_weight']) {
      unset($element['_weight']);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as $key => $value) {
      if (isset($value['level_counter']) && $value['level_counter'] > 0) {
        $values[$key]['value'] = $value['level' . ($value['level_counter'] - 1)];
      }
      else {
        $values[$key]['value'] = $value['level0'];
      }
    }

    return $values;
  }

}

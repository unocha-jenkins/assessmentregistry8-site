<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
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
    // Check form state for Ajax changes.
    if ($form_state->get('level')) {
      if ($form_state->get('delta') == $delta) {
        switch ($form_state->get('level')) {
          case 'l0':
            $items[$delta]->level0 = $form_state->get('value');
            break;

          case 'l1':
            $items[$delta]->level1 = $form_state->get('value');
            break;

        }
      }
    }

    $element['level0'] = [
      '#type' => 'select',
      '#options' => ocha_locations_allowed_values_by_parent(),
      '#title' => $this->t('Level 0'),
      '#default_value' => isset($items[$delta]->level0) ? $items[$delta]->level0 : '',
      '#empty_option' => $this->t('- Select admin level 0 -'),
      '#delta' => $delta,
      '#level' => 'l0',
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

    if (isset($items[$delta]->level0) && !empty($items[$delta]->level0)) {
      $options = ocha_locations_allowed_values_by_parent($items[$delta]->level0);
      if (count($options)) {
        $element['level1'] = [
          '#type' => 'select',
          '#options' => $options,
          '#title' => $this->t('Level 1'),
          '#default_value' => isset($items[$delta]->level1) ? $items[$delta]->level1 : '',
          '#empty_option' => $this->t('- Select admin level 1 -'),
          '#delta' => $delta,
          '#level' => 'l1',
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

        if (isset($items[$delta]->level1) && !empty($items[$delta]->level1)) {
          $options = ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0);
          if (count($options)) {
            $element['level2'] = [
              '#type' => 'select',
              '#options' => ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0),
              '#title' => $this->t('Level 2'),
              '#default_value' => isset($items[$delta]->level2) ? $items[$delta]->level2 : '',
              '#empty_option' => $this->t('- Select admin level 2 -'),
            ];
          }
        }
      }
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
      if (empty($value['level0'])) {
        unset($values[$key]['level0']);
      }
      if (empty($value['level1'])) {
        unset($values[$key]['level1']);
      }
      if (empty($value['level2'])) {
        unset($values[$key]['level2']);
      }
    }

    return $values;
  }

}

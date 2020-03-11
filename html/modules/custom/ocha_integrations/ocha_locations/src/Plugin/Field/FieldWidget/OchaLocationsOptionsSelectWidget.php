<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

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
class OchaLocationsOptionsSelectWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(true);
dpm($delta, 'build delta');

dpm($form_state->get('level'), 'state - level');
dpm($form_state->get('delta'), 'state - delta');
dpm($form_state->get('value'), 'state - value');

    if ($form_state->get('level')) {
      if ($form_state->get('delta') == $delta) {
        switch ($form_state->get('level')) {
          case 'l0':
            dpm($items[$delta]->level0 . ' - ' . $form_state->get('value'), 'old - new');
            $items[$delta]->level0 = $form_state->get('value');
            break;

          case 'l1':
            dpm($items[$delta]->level1 . ' - ' . $form_state->get('value'), 'old - new');
            $items[$delta]->level1 = $form_state->get('value');
            break;

          case 'l2':
            dpm($items[$delta]->level2 . ' - ' . $form_state->get('value'), 'old - new');
            $items[$delta]->level2 = $form_state->get('value');
            break;

        }
      }
    }

    $element['level0'] = [
      '#type' => 'select',
      '#options' => ocha_locations_allowed_values_by_parent(),
      '#title' => t('Level 0'),
      '#default_value' => isset($items[$delta]->level0) ? $items[$delta]->level0 : '',
      '#empty_option' => t(' - Select admin level 0 - '),
      '#delta' => $delta,
      '#level' => 'l0',
      '#element_validate' => [
        [$this, 'validate'],
      ],
      '#ajax' => [
        'callback' => [$this, 'changeLevel'],
        'event' => 'change',
        'wrapper' => 'ocha-location-wrapper-' . $delta,
        'progress' => array(
          'type' => 'throbber',
        ),
      ]
    ];

    if (isset($items[$delta]->level0) && !empty($items[$delta]->level0)) {
      $options = ocha_locations_allowed_values_by_parent($items[$delta]->level0);
      dpm('level 1');
      if (count($options)) {
        $element['level1'] = [
          '#type' => 'select',
          '#options' => $options,
          '#title' => t('Level 1'),
          '#default_value' => isset($items[$delta]->level1) ? $items[$delta]->level1 : '',
          '#empty_option' => t(' - Select admin level 1 - '),
          '#delta' => $delta,
          '#level' => 'l1',
          '#element_validate' => [
            [$this, 'validate'],
          ],
          '#ajax' => [
            'callback' => [$this, 'changeLevel'],
            'event' => 'change',
            'wrapper' => 'ocha-location-wrapper-' . $delta,
            'progress' => array(
              'type' => 'throbber',
            ),
          ]
        ];

        if (isset($items[$delta]->level1) && !empty($items[$delta]->level1)) {
          dpm('level 2');
          $options = ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0);
          if (count($options)) {
            $element['level2'] = [
              '#type' => 'select',
              '#options' => ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0),
              '#title' => t('Level 2'),
              '#default_value' => isset($items[$delta]->level2) ? $items[$delta]->level2 : '',
              '#empty_option' => t(' - Select admin level 2 - '),
              '#delta' => $delta,
              '#level' => 'l2',
              '#element_validate' => [
                [$this, 'validate'],
              ],
              '#ajax' => [
                'callback' => [$this, 'changeLevel'],
                'event' => 'change',
                'wrapper' => 'ocha-location-wrapper-' . $delta,
                'progress' => array(
                  'type' => 'throbber',
                ),
              ]
            ];
          }
        }
      }
    }

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1) {
      $element += array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('container-inline')),
      );
    }

    $element['#prefix'] = '<div id="ocha-location-wrapper-'  . $delta . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  public function validate($element, FormStateInterface $form_state) {
    // Act on triggering element only.
    $triggering_element = $form_state->getTriggeringElement();

    if (isset($triggering_element['#level'])) {
      dpm('trigger');
      $form_state->set('level', $triggering_element['#level']);
      $form_state->set('delta', $triggering_element['#delta']);
      $form_state->set('value', $triggering_element['#value']);
      $form_state->setRebuild();
    }
  }

  public function changeLevel(array &$form, FormStateInterface &$form_state){
    $element = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    dpm($element['#array_parents']);
    $element = NestedArray::getValue($form, array_slice($element['#array_parents'], 0, -1));

    return $element;
  }

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

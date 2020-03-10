<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldWidget;

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
    $element['level0'] = array(
      '#type' => 'select',
      '#options' => ocha_locations_allowed_values_by_parent(),
      '#title' => t('Level 0'),
      '#default_value' => isset($items[$delta]->level0) ? $items[$delta]->level0 : '',
      '#empty_option' => t(' - Select admin level 0 - '),
    );

    if (isset($items[$delta]->level0)) {
      $options = ocha_locations_allowed_values_by_parent($items[$delta]->level0);
      if (count($options)) {
        $element['level1'] = array(
          '#type' => 'select',
          '#options' => $options,
          '#title' => t('Level 1'),
          '#default_value' => isset($items[$delta]->level1) ? $items[$delta]->level1 : '',
          '#empty_option' => t(' - Select admin level 1 - '),
        );

        if (isset($items[$delta]->level1)) {
          $options = ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0);
          if (count($options)) {
            $element['level2'] = array(
              '#type' => 'select',
              '#options' => ocha_locations_allowed_values_by_parent($items[$delta]->level1, $items[$delta]->level0),
              '#title' => t('Level 2'),
              '#default_value' => isset($items[$delta]->level2) ? $items[$delta]->level2 : '',
              '#empty_option' => t(' - Select admin level 2 - '),
            );
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

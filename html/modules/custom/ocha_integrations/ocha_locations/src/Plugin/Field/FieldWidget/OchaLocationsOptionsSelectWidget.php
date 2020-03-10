<?php

namespace Drupal\ocha_locations\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
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
dpm('build');
dpm($form_state->get('level0'), 'state');
    if ($form_state->get('level0')) {
      $items[$delta]->level0 = $form_state->get('level0');
    }

    $element['level0'] = array(
      '#type' => 'select',
      '#options' => ocha_locations_allowed_values_by_parent(),
      '#title' => t('Level 0'),
      '#default_value' => isset($items[$delta]->level0) ? $items[$delta]->level0 : '',
      '#empty_option' => t(' - Select admin level 0 - '),
      '#ajax' => [
        'callback' => [$this, 'changeLevel0'],
        'event' => 'change',
        'wrapper' => 'ocha-location-wrapper',
        'progress' => array(
          'type' => 'throbber',
        ),
      ]
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
    if (FALSE && $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1) {
      $element += array(
        '#type' => 'fieldset',
        '#attributes' => array('class' => array('container-inline')),
      );
    }

    $element['#prefix'] = '<div id="ocha-location-wrapper">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  public function changeLevel0(array &$form, FormStateInterface &$form_state){
    dpm('changeLevel0');
    $triggeringElement = $form_state->getTriggeringElement();
    $form_state->set('level0', $triggeringElement['#value']);
    $form_state->setRebuild();

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('ocha-location-wrapper', $triggeringElement));

    return $response;
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

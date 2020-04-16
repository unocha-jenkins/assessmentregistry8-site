<?php

namespace Drupal\custom_widgets\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityFormInterface;

/**
 * Plugin implementation of the 'existing_autocomplete_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "custom_widgets_text_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   multiple_values = TRUE,
 *   field_types = {
 *     "list_string"
 *   }
 * )
 */
class TextAutocompleteWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_items' => 15,
      'matching_method' => 'contains',
      'use_select2' => 'no',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['max_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Max number of results to return.'),
      '#default_value' => $this->getSetting('max_items'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['matching_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Matching method'),
      '#default_value' => $this->getSetting('matching_method'),
      '#options' => [
        'contains' => $this->t('Contains'),
        'beginswith' => $this->t('Begins with'),
      ],
      '#required' => TRUE,
    ];

    if (custom_widgets_select2_available()) {
      $elements['use_select2'] = [
        '#type' => 'select',
        '#title' => $this->t('Use select 2'),
        '#default_value' => $this->getSetting('use_select2'),
        '#options' => [
          'yes' => $this->t('Yes'),
          'no' => $this->t('No'),
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Method: @matching_method', ['@matching_method' => $this->getSetting('matching_method')]);
    if ($this->getSetting('use_select2') == 'yes') {
      $summary[] = $this->t('Using select2');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function getOptions($entity_type_id, $field_name) {
    return custom_widgets_get_allowed_options($entity_type_id, $field_name);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->getSetting('use_select2') == 'yes' && custom_widgets_select2_available()) {
      $options = $this::getOptions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getName());
      $default_values = [];
      foreach ($items as $item) {
        $default_values[] = $item->value;
      }

      $element['value'] = $element + [
        '#type' => 'select2',
        '#multiple' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1,
        '#validated' => TRUE,
        '#element_validate' => [
          [$this, 'validateElement'],
        ],
        '#empty_value' => '',
        '#options' => $options,
        '#default_value' => $default_values,
        '#autocomplete' => TRUE,
        '#autocomplete_options_callback' => '\Drupal\custom_widgets\Plugin\Field\FieldWidget\TextAutocompleteWidget::getValidSelectedOptions',
        '#autocomplete_route_callback' => '\Drupal\custom_widgets\Plugin\Field\FieldWidget\TextAutocompleteWidget::setAutocompleteRouteParameters',
        '#maximumSelectionLength' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1 ? $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() : 0,
        '#route_settings' => [
          'field_name' => $this->fieldDefinition->getName(),
          'count' => $this->getSetting('max_items'),
          'entity_type_id' => $this->fieldDefinition->getTargetEntityTypeId(),
          'matching_method' => $this->getSetting('matching_method'),
          'uid' => custom_widgets_current_user_uid(),
          'hash' => '',
        ],
      ];
      $element['#options'] = $options;

      // Add a hash.
      $element['value']['#route_settings']['hash'] = custom_widgets_calculate_hash($element['value']['#route_settings']);
    }
    else {
      $options = $this::getOptions($this->fieldDefinition->getTargetEntityTypeId(), $this->fieldDefinition->getName());
      $default_values = [];
      foreach ($items as $item) {
        if (isset($options[$item->value])) {
          $default_values[] = $options[$item->value] . ' (' . $item->value . ')';
        }
      }

      $element['value'] = $element + [
        '#type' => 'textfield',
        '#multiple' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1,
        '#cardinalitty' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality(),
        '#tags' => $this->fieldDefinition->getFieldStorageDefinition()->getCardinality() != 1,
        '#default_value' => implode(', ', $default_values),
        '#autocomplete_options_callback' => '\Drupal\custom_widgets\Plugin\Field\FieldWidget\TextAutocompleteWidget::getValidSelectedTextOptions',
        '#autocomplete_route_name' => 'custom_widgets.text_autocomplete',
        '#autocomplete_route_parameters' => [
          'field_name' => $items->getName(),
          'count' => $this->getSetting('max_items'),
          'entity_type_id' => $this->fieldDefinition->getTargetEntityTypeId(),
          'matching_method' => $this->getSetting('matching_method'),
          'uid' => custom_widgets_current_user_uid(),
          'hash' => '',
        ],
      ];

      // Add a hash.
      $element['value']['#autocomplete_route_parameters']['hash'] = custom_widgets_calculate_hash($element['value']['#autocomplete_route_parameters']);
    }

    return $element;
  }

  /**
   * Get an array of currently selected options.
   *
   * @param array $element
   *   The render element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   Key => entity ID, Value => entity label.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getValidSelectedOptions(array $element, FormStateInterface $form_state) {
    $selected_options = [];
    $options = TextAutocompleteWidget::getOptions($element['#route_settings']['entity_type_id'], $element['#route_settings']['field_name']);

    if ($form_state->getFormObject() instanceof ContentEntityFormInterface) {
      $entity = $form_state->getformObject()->getEntity();
      foreach ($entity->get($element['#route_settings']['field_name']) as $item) {
        if ($item->value !== NULL && isset($options[$item->value])) {
          $selected_options[$item->value] = $options[$item->value];
        }
      }
    }

    return $selected_options;
  }

  /**
   * Sets the autocomplete route parameters.
   *
   * @param array $element
   *   The render element.
   *
   * @return array
   *   The render element with autocomplete route parameters.
   */
  public static function setAutocompleteRouteParameters(array &$element) {
    $element['#autocomplete_route_name'] = 'custom_widgets.text_autocomplete_select2';
    $element['#autocomplete_route_parameters'] = [
      'field_name' => $element['#route_settings']['field_name'],
      'count' => $element['#route_settings']['count'],
      'entity_type_id' => $element['#route_settings']['entity_type_id'],
      'matching_method' => $element['#route_settings']['matching_method'],
      'uid' => $element['#route_settings']['uid'],
      'hash' => $element['#route_settings']['hash'],
    ];

    if (empty($element['#autocomplete_route_parameters']['hash'])) {
      $element['#autocomplete_route_parameters']['hash'] = custom_widgets_calculate_hash($element['#autocomplete_route_parameters']);
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateElement(array $element, FormStateInterface $form_state) {
    if ($element['#required'] && $element['#value'] == '') {
      $form_state->setError($element, $this->t('The @name field is required.', ['@name' => $element['#title']]));
    }

    if (is_array($element['#value'])) {
      $values = array_values($element['#value']);
    }
    else {
      $values = [$element['#value']];
    }

    // Filter out the '' option. Use a strict comparison, because
    // 0 == 'any string'.
    $index = array_search('', $values, TRUE);
    if ($index !== FALSE) {
      unset($values[$index]);
    }

    // Check if value exists as an option.
    $options = TextAutocompleteWidget::getOptions($element['#route_settings']['entity_type_id'], $element['#route_settings']['field_name']);
    foreach ($values as $index => $value) {
      if (!isset($options[$value])) {
        unset($values[$value]);
        $form_state->setError($element, $this->t('The @name field has an illegal option.', ['@name' => $element['#title']]));
      }
    }

    if ($element['#required'] && count($values) == 0) {
      $form_state->setError($element, $this->t('The @name field is required.', ['@name' => $element['#title']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $items = [];
    if ($this->getSetting('use_select2') == 'yes' && custom_widgets_select2_available()) {
      if (!is_array($values['value'])) {
        $values['value'] = [$values['value']];
      }

      foreach ($values['value'] as $value) {
        if (!empty($value)) {
          $items[] = ['value' => trim($value)];
        }
      }
    }
    else {
      // Tags style.
      $values = explode(',', $values['value']);
      $items = [];
      foreach ($values as $value) {
        if (!empty($value)) {
          $pos = strrpos($value, '(');
          $items[] = ['value' => substr($value, $pos + 1, -1)];
        }
      }
    }

    return $items;
  }

}

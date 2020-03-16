<?php

namespace Drupal\ocha_assessment_document\Plugin\Field\FieldWidget;

use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Plugin implementation of the 'ocha_assessment_document' widget.
 *
 * @FieldWidget (
 *   id = "ocha_assessment_document_widget",
 *   label = @Translation("OCHA assessment document widget"),
 *   field_types = {
 *     "ocha_assessment_document"
 *   }
 * )
 */
class OchaAssessmentDocumentWidget extends FileWidget {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $elements = parent::formMultipleElements($items, $form, $form_state);

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['accessibility'] = [
      '#type' => 'select',
      '#title' => $this->t('Accessibility'),
      '#options' => [
        'Publicly Available' => 'Publicly Available',
        'Available on Request' => 'Available on Request',
        'Restricted Distribution' => 'Restricted Distribution',
        'Not Available' => 'Not Available',
        'Not Applicable' => 'Not Applicable',
      ],
      '#default_value' => $items[$delta]->accessibility,
      '#weight' => 10,
    ];

    $element['uri'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#default_value' => $items[$delta]->uri,
      '#maxlength' => 2048,
      '#required' => $element['#required'],
      '#link_type' => $this->getFieldSetting('link_type'),
      '#description' => $this->t('This must be an external URL such as %url.', ['%url' => 'http://example.com']),
      '#weight' => 20,
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $items[$delta]->title,
      '#maxlength' => 255,
      '#weight' => 21,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = parent::massageFormValues($values, $form, $form_state);

    return $new_values;
  }

}

<?php

namespace Drupal\ocha_docstore_files\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\Plugin\Field\FieldWidget\FileWidget;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'ocha_doc_store_file_widget' widget.
 *
 * @FieldWidget(
 *   id = "ocha_doc_store_file_widget",
 *   module = "ocha_docstore_files",
 *   label = @Translation("OCHA Document store file widget"),
 *   field_types = {
 *     "ocha_doc_store_file"
 *   }
 * )
 */
class OchaDocStoreFileWidget extends FileWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'endpoint' => 'http://docstore.local.docksal/api/v1/files',
      'api-key' => 'abcd',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API end point'),
      '#default_value' => $this->getSetting('endpoint'),
      '#weight' => 17,
    ];

    $element['api-key'] = [
      '#type' => 'textfield',
      '#title' => t('API key'),
      '#default_value' => $this->getSetting('api-key'),
      '#weight' => 18,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Progress indicator: @progress_indicator', ['@progress_indicator' => $this->getSetting('progress_indicator')]);
    $summary[] = t('Endpoint: @endpoint', ['@endpoint' => $this->getSetting('endpoint')]);

    return $summary;
  }

  /**
   * Overrides \Drupal\Core\Field\WidgetBase::formMultipleElements().
   *
   * Special handling for draggable multiple widgets and 'add more' button.
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }

    $elements = [
      'existing_files' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Existing files'),
      ],
      'queued_files' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Queued files'),
        '#access' => FALSE,
      ],
      'add_files' => [
        '#type' => 'fieldset',
        '#title' => $this->t('Add files'),
        'from_local' => [
          '#type' => 'fieldset',
          '#title' => $this->t('Local file(s)'),
          'local_file' => [
            '#type' => 'file',
            '#name' => 'files[]',
            '#multiple' => TRUE,
            '#error_no_message' => TRUE,
          ],
          'upload' => [
            '#type' => 'button',
            '#value' => $this->t('Upload file(s)'),
            '#ajax' => [
              'callback' => [get_called_class(), 'uploadAjaxCallback'],
              'wrapper' => $ajax_wrapper_id,
              'effect' => 'fade',
              'progress' => [
                'type' => 'throbber',
                'message' => NULL,
              ],
            ],
          ],
        ],
        'from_uri' => [
          '#type' => 'fieldset',
          '#title' => $this->t('External URL(s)'),
          'uri' => [
            '#type' => 'textarea',
            '#rows' => 2,
            '#description' => $this->t('One URL per line.'),
          ],
          'fetch' => [
            '#type' => 'button',
            '#value' => $this->t('Add file(s)'),
            '#ajax' => [
              'callback' => [get_called_class(), 'remoteAjaxCallback'],
              'wrapper' => $ajax_wrapper_id,
              'effect' => 'fade',
              'progress' => [
                'type' => 'throbber',
                'message' => NULL,
              ],
            ],
          ],
        ],
      ],
    ];

    $delta = 0;
    // Add an element for every existing item.
    foreach ($items as $item) {
      // Add existing files.
      if (isset($item->media_uuid)) {
        $elements['existing_files'][$delta] = [
          'filelink' => [
            '#type' => 'link',
            '#title' => $item->filename,
            '#url' => Url::fromUri($item->uri),
          ],
          'remove' => [
            '#type' => 'button',
            '#value' => $this->t('Remove file'),
          ],
        ];
      }
      elseif (isset($item->filename)) {
        $elements['queued_files']['#access'] = TRUE;
        $elements['queued_files'][$delta] = [
          'filelink' => [
            '#type' => 'link',
            '#title' => $item->filename,
            '#url' => Url::fromUri($item->uri),
          ],
          'remove' => [
            '#type' => 'button',
            '#value' => $this->t('Remove file'),
          ],
        ];
      }

      $delta++;
    }

    $elements['#tree'] = TRUE;
    $elements['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $elements['#suffix'] = '</div>';

    return $elements;
  }

  /**
   * #ajax callback for file upload.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public static function uploadAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    // Get uploaded files.
    $all_files = \Drupal::request()->files->get('files', []);

    // Add 'dummy' items.
    foreach ($all_files as $field) {
      foreach ($field as $file_info) {
        $original_file_name = trim($file_info->getClientOriginalName(), '.');
        $file_tmp = $file_info->getRealPath();
      }
    }

    // Rebuild complete element form, so newly uploaded files are added to queued_files.
    return [];
  }

  /**
   * #ajax callback for remote files.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The ajax response of the ajax upload.
   */
  public static function remoteAjaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    // Get URLs.

    // Add 'dummy' items.
    // Extract filename from URL.

    // Rebuild complete element form, so remote files are added to queued_files.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);

    // Update reference to 'items' stored during upload to take into account
    // changes to values like 'alt' etc.
    // @see \Drupal\file\Plugin\Field\FieldWidget\FileWidget::submit()
    $field_name = $this->fieldDefinition->getName();
    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    $field_state['items'] = $items->getValue();
    static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }

  /**
   * Form submission handler for upload/remove button of formElement().
   */
  public static function submit($form, FormStateInterface $form_state) {
    // During the form rebuild, formElement() will create field item widget
    // elements using re-indexed deltas, so clear out FormState::$input to
    // avoid a mismatch between old and new deltas. The rebuilt elements will
    // have #default_value set appropriately for the current state of the field,
    // so nothing is lost in doing this.
    $button = $form_state->getTriggeringElement();
    $parents = array_slice($button['#parents'], 0, -2);
    NestedArray::setValue($form_state->getUserInput(), $parents, NULL);

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#field_parents'];

    $submitted_values = NestedArray::getValue($form_state->getValues(), array_slice($button['#parents'], 0, -2));
    foreach ($submitted_values as $delta => $submitted_value) {
      if (empty($submitted_value['uuids'])) {
        unset($submitted_values[$delta]);
      }
    }

    // If there are more files uploaded via the same widget, we have to separate
    // them, as we display each file in its own widget.
    $new_values = [];
    foreach ($submitted_values as $delta => $submitted_value) {
      if (is_array($submitted_value['uuids'])) {
        foreach ($submitted_value['uuids'] as $uuid) {
          $new_value = $submitted_value;
          $new_value['uuids'] = [$uuid];
          $new_values[] = $new_value;
        }
      }
      else {
        $new_value = $submitted_value;
      }
    }

    // Re-index deltas after removing empty items.
    $submitted_values = array_values($new_values);

    // Update form_state values.
    NestedArray::setValue($form_state->getValues(), array_slice($button['#parents'], 0, -2), $submitted_values);

    // Update items.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items'] = $submitted_values;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);
  }

}

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

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\MessageCommand;

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

    $field_state = static::getWidgetState($form['#parents'], $field_name, $form_state);
    if (!isset($field_state['queued_files'])) {
      $field_state['queued_files'] = [];
    }

    // Check for AJAX data.
    $trigger = $form_state->getTriggeringElement();
    if ($trigger && isset($trigger['#method'])) {
      $element_parents = $trigger['#parents'];
      array_pop($element_parents);

      if ($trigger['#method'] === 'fetch_files') {
        $element_parents[] = 'uri';
        $from_uri = $form_state->getValue($element_parents);
        if (!empty($from_uri)) {
          $uris = explode("\n", $from_uri);
          foreach ($uris as $uri) {
            $uri = trim($uri);
            if (empty($uri)) {
              continue;
            }

            $field_state['queued_files'][] = [
              'filename' => basename(parse_url($uri, PHP_URL_PATH)),
              'uri' => $uri,
            ];
          }

          static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
        }
      }
      elseif ($trigger['#method'] === 'local_files') {
        // Get uploaded files.
        $all_files = \Drupal::request()->files->get('files', []);

        // Add files without uploading.
        foreach ($all_files as $field) {
          foreach ($field as $file_info) {
            $field_state['queued_files'][] = [
              'filename' => trim($file_info->getClientOriginalName(), '.'),
              'tmp_path' => $file_info->getRealPath(),
            ];
          }
        }

        static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
      }
      elseif ($trigger['#method'] === 'remove_queue') {
        // Get index from parents.
        $index = $trigger['#index'];

        if (isset($field_state['queued_files'][$index])) {
          unset($field_state['queued_files'][$index]);

          // Re-key the array.
          $field_state['queued_files'] = array_values($field_state['queued_files']);
          static::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
        }
      }
    }

    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');
/*
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    if (isset($field_state['items'])) {
      $items->setValue($field_state['items']);
    }
*/
    $elements = [
      '#process' => [[get_class($this), 'process']],
      '#queue' => ['test'],
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
            '#method' => 'local_files',
            '#value' => $this->t('Upload file(s)'),
            '#ajax' => [
              'callback' => [get_called_class(), 'rebuildWidgetFormLocal'],
              'options' => [
                'query' => [
                  'element_parents' => implode('/', $parents),
                ],
              ],
              'wrapper' => $ajax_wrapper_id,
              'effect' => 'fade',
              'progress' => [
                'type' => 'throbber',
                'message' => $this->t('Uploading files'),
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
            '#method' => 'fetch_files',
            '#value' => $this->t('Add file(s)'),
            '#ajax' => [
              'callback' => [get_called_class(), 'rebuildWidgetFormRemote'],
              'options' => [
                'query' => [
                  'element_parents' => implode('/', $parents),
                ],
              ],
              'wrapper' => $ajax_wrapper_id,
              'effect' => 'fade',
              'progress' => [
                'type' => 'throbber',
                'message' => $this->t('Fetching files'),
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
            '#name' => 'remove-existing-' . $delta,
          ],
        ];
      }

      $delta++;
    }

    // Add all queued files.
    foreach ($field_state['queued_files'] as $delta => $queued_file) {
      $elements['queued_files']['#access'] = TRUE;
      if (isset($queued_file['uri'])) {
        $elements['queued_files'][] = [
          'filelink' => [
            '#type' => 'link',
            '#title' => $queued_file['filename'],
            '#url' => Url::fromUri($queued_file['uri']),
          ],
          'remove' => [
            '#type' => 'button',
            '#value' => 'Remove queued file',
            '#name' => 'remove-queued-' . $delta,
          ],
        ];
      }
      else {
        $elements['queued_files'][] = [
          'filelink' => [
            '#type' => 'markup',
            '#markup' => $queued_file['filename'],
          ],
          'remove' => [
            '#type' => 'button',
            '#value' => 'Remove queued file',
            '#name' => 'remove-queued-' . $delta,
          ],
        ];
      }
    }

    $elements['#tree'] = TRUE;
    $elements['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $elements['#suffix'] = '</div>';

    return $elements;
  }

  /**
   * Form API callback.
   */
  public static function process($element, FormStateInterface $form_state, $form) {
    $element['add_files']['from_uri']['fetch']['#ajax']['options'] = [
      'query' => [
        'element_parents' => implode('/', $element['#array_parents']),
      ],
    ];

    $element['add_files']['from_local']['upload']['#ajax']['options'] = [
      'query' => [
        'element_parents' => implode('/', $element['#array_parents']),
      ],
    ];

    if (isset($element['queued_files']) && $element['queued_files']['#access']) {
      foreach ($element['queued_files'] as $delta => $queued_file) {
        if (strpos($delta, '#') !== FALSE) {
          continue;
        }

        $element['queued_files'][$delta]['remove']['#method'] = 'remove_queue';
        $element['queued_files'][$delta]['remove']['#index'] = $delta;

        $element['queued_files'][$delta]['remove']['#ajax'] = [
          'callback' => [get_called_class(), 'rebuildWidgetForm'],
          'options' => [
            'query' => [
              'element_parents' => implode('/', $element['#array_parents']),
            ],
          ],
          'wrapper' => $element['add_files']['from_local']['upload']['#ajax']['wrapper'],
          'effect' => 'fade',
          'progress' => [
            'type' => 'throbber',
            'message' => NULL,
          ],
        ];
      }
    }
    return $element;
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
  public static function rebuildWidgetFormRemote(&$form, FormStateInterface &$form_state, Request $request) {
    // Remove entered URLs.
    $form['add_files']['from_uri']['uri']['#value'] = '';

    return static::rebuildWidgetForm($form, $form_state, $request);
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
  public static function rebuildWidgetFormLocal(&$form, FormStateInterface &$form_state, Request $request) {
    return static::rebuildWidgetForm($form, $form_state, $request);
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
  public static function rebuildWidgetForm(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // Rebuild complete element form, so remote files are added to queued_files.
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
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

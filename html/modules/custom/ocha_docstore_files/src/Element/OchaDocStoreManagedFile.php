<?php

namespace Drupal\ocha_docstore_files\Element;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\File as FileElement;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\Request;
use Drupal\file\Element\ManagedFile;

/**
 * Provides an AJAX/progress aware widget for uploading and saving a file.
 *
 * @FormElement("ocha_docstore_managed_file")
 */
class OchaDocStoreManagedFile extends FileElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#process' => [
        [$class, 'processFile'],
      ],
      '#size' => 60,
      '#pre_render' => [
        [$class, 'preRenderFile'],
      ],
      '#theme' => 'input__file',
      '#theme_wrappers' => ['form_element'],
      '#endpoint' => FALSE,
      '#apikey' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    // Find the current value of this field.
    $uuids = !empty($input['uuids']) ? explode(' ', $input['uuids']) : [];
    $force_default = FALSE;

    // Process any input and save new uploads.
    if ($input !== FALSE) {
      $input['uuids'] = $uuids;
      $return = $input;

      // Uploads take priority over all other values.
      $upload_name = implode('_', $element['#parents']);
      $all_files = \Drupal::request()->files->get('files', []);

      if (!empty($all_files[$upload_name])) {
        $uploaded_files = $all_files[$upload_name];

        // @todo return temp file and original name.
        $files = [];
        foreach ($uploaded_files as $i => $file_info) {
          $user = \Drupal::currentUser();
          $original_file_name = trim($file_info->getClientOriginalName(), '.');
          $file_tmp = $file_info->getRealPath();

          if ($blob = fopen($file_tmp, "rb")) {
            $contents = fread($blob, filesize($file_tmp));
            $response = \Drupal::httpClient()->request(
              'POST',
              $element['#endpoint'],
              [
                'body' => json_encode([
                  'filename' => $original_file_name,
                  'data' => base64_encode($contents),
                  'private' => FALSE,
                ]),
                'headers' => [
                  'API-KEY' => $element['#apikey'],
                ],
              ]
            );

            $body = $response->getBody() . '';
            $body = json_decode($body);
            $uuids[] = $body->uuid;

            fclose($blob);
          }
        }
      }
    }

    // If there is no input or if the default value was requested above, use the
    // default value.
    if ($input === FALSE || $force_default) {
      if ($element['#extended']) {
        $default_uuids = isset($element['#default_value']['uuids']) ? $element['#default_value']['uuids'] : [];
        $return = isset($element['#default_value']) ? $element['#default_value'] : ['uuids' => []];
      }
      else {
        $default_uuids = isset($element['#default_value']) ? $element['#default_value'] : [];
        $return = ['uuids' => []];
      }

      // Confirm that the file exists when used as a default value.
      $uuids = $default_uuids;
    }

    $return['uuids'] = $uuids;
    return $return;
  }

  /**
   * #ajax callback for managed_file upload forms.
   *
   * This ajax callback takes care of the following things:
   *   - Ensures that broken requests due to too big files are caught.
   *   - Adds a class to the response to be able to highlight in the UI, that a
   *     new file got uploaded.
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
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    // Add the special AJAX class if a new file was added.
    $current_file_count = $form_state->get('file_upload_delta_initial');
    if (isset($form['#file_upload_delta']) && $current_file_count < $form['#file_upload_delta']) {
      $form[$current_file_count]['#attributes']['class'][] = 'ajax-new-content';
    }
    // Otherwise just add the new content class on a placeholder.
    else {
      $form['#suffix'] .= '<span class="ajax-new-content"></span>';
    }

    $status_messages = ['#type' => 'status_messages'];
    $form['#prefix'] .= $renderer->renderRoot($status_messages);
    $output = $renderer->renderRoot($form);

    $response = new AjaxResponse();
    $response->setAttachments($form['#attached']);

    return $response->addCommand(new ReplaceCommand(NULL, $output));
  }

  /**
   * Render API callback: Expands the managed_file element type.
   *
   * Expands the file type to include Upload and Remove buttons, as well as
   * support for a default value.
   */
  public static function processManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {

    // This is used sometimes so let's implode it just once.
    $parents_prefix = implode('_', $element['#parents']);

    $uuids = isset($element['#value']['uuids']) ? $element['#value']['uuids'] : [];

    // Set some default element properties.
    $element['#progress_indicator'] = empty($element['#progress_indicator']) ? 'none' : $element['#progress_indicator'];
    $element['#files'] = !empty($uuids) ? File::loadMultiple($uuids) : [];
    $element['#tree'] = TRUE;

    // Generate a unique wrapper HTML ID.
    $ajax_wrapper_id = Html::getUniqueId('ajax-wrapper');

    $ajax_settings = [
      'callback' => [get_called_class(), 'uploadAjaxCallback'],
      'options' => [
        'query' => [
          'element_parents' => implode('/', $element['#array_parents']),
        ],
      ],
      'wrapper' => $ajax_wrapper_id,
      'effect' => 'fade',
      'progress' => [
        'type' => $element['#progress_indicator'],
        'message' => $element['#progress_message'],
      ],
    ];

    // Set up the buttons first since we need to check if they were clicked.
    $element['upload_button'] = [
      '#name' => $parents_prefix . '_upload_button',
      '#type' => 'submit',
      '#value' => t('Upload'),
      '#attributes' => ['class' => ['js-hide']],
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => -5,
    ];

    // Force the progress indicator for the remove button to be either 'none' or
    // 'throbber', even if the upload button is using something else.
    $ajax_settings['progress']['type'] = ($element['#progress_indicator'] == 'none') ? 'none' : 'throbber';
    $ajax_settings['progress']['message'] = NULL;
    $ajax_settings['effect'] = 'none';
    $element['remove_button'] = [
      '#name' => $parents_prefix . '_remove_button',
      '#type' => 'submit',
      '#value' => $element['#multiple'] ? t('Remove selected') : t('Remove'),
      '#validate' => [],
      '#submit' => ['file_managed_file_submit'],
      '#limit_validation_errors' => [$element['#parents']],
      '#ajax' => $ajax_settings,
      '#weight' => 1,
    ];

    $element['uuids'] = [
      '#type' => 'hidden',
      '#value' => $uuids,
    ];

    // Add progress bar support to the upload if possible.
    if ($element['#progress_indicator'] == 'bar' && $implementation = file_progress_implementation()) {
      $upload_progress_key = mt_rand();

      if ($implementation == 'uploadprogress') {
        $element['UPLOAD_IDENTIFIER'] = [
          '#type' => 'hidden',
          '#value' => $upload_progress_key,
          '#attributes' => ['class' => ['file-progress']],
          // Uploadprogress extension requires this field to be at the top of
          // the form.
          '#weight' => -20,
        ];
      }

      // Add the upload progress callback.
      $element['upload_button']['#ajax']['progress']['url'] = Url::fromRoute('file.ajax_progress', ['key' => $upload_progress_key]);

      // Set a custom submit event so we can modify the upload progress
      // identifier element before the form gets submitted.
      $element['upload_button']['#ajax']['event'] = 'fileUpload';
    }

    // Use a manually generated ID for the file upload field so the desired
    // field label can be associated with it below. Use the same method for
    // setting the ID that the form API autogenerator does.
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm()
    $id = Html::getUniqueId('edit-' . implode('-', array_merge($element['#parents'], ['upload'])));

    // The file upload field itself.
    $element['upload'] = [
      '#name' => 'files[' . $parents_prefix . ']',
      '#type' => 'file',
      // This #title will not actually be used as the upload field's HTML label,
      // since the theme function for upload fields never passes the element
      // through theme('form_element'). Instead the parent element's #title is
      // used as the label (see below). That is usually a more meaningful label
      // anyway.
      '#title' => t('Choose a file'),
      '#title_display' => 'invisible',
      '#id' => $id,
      '#size' => $element['#size'],
      '#multiple' => $element['#multiple'],
      '#theme_wrappers' => [],
      '#weight' => -10,
      '#error_no_message' => TRUE,
    ];
    if (!empty($element['#accept'])) {
      $element['upload']['#attributes'] = ['accept' => $element['#accept']];
    }

    // Indicate that $element['#title'] should be used as the HTML label for the
    // file upload field.
    $element['#label_for'] = $element['upload']['#id'];

    if (!empty($element['#value']['filename'])) {
      $delta = 0;
      $file_link = [
        '#type' => 'link',
        '#title' => $element['#value']['filename'],
        '#url' => Url::fromUri($element['#value']['uri']),
      ];

      if ($element['#multiple']) {
        $element['file_' . $delta]['selected'] = [
          '#type' => 'checkbox',
          '#title' => \Drupal::service('renderer')->renderPlain($file_link),
        ];
      }
      else {
        $element['file_' . $delta]['filename'] = $file_link + ['#weight' => -10];
      }
    }

    // Add the extension list to the page as JavaScript settings.
    if (isset($element['#upload_validators']['file_validate_extensions'][0])) {
      $extension_list = implode(',', array_filter(explode(' ', $element['#upload_validators']['file_validate_extensions'][0])));
      $element['upload']['#attached']['drupalSettings']['file']['elements']['#' . $id] = $extension_list;
    }

    // Prefix and suffix used for Ajax replacement.
    $element['#prefix'] = '<div id="' . $ajax_wrapper_id . '">';
    $element['#suffix'] = '</div>';

    return $element;
  }

  /**
   * Render API callback: Hides display of the upload or remove controls.
   *
   * Upload controls are hidden when a file is already uploaded. Remove controls
   * are hidden when there is no file attached. Controls are hidden here instead
   * of in \Drupal\file\Element\ManagedFile::processManagedFile(), because
   * #access for these buttons depends on the managed_file element's #value. See
   * the documentation of \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   * for more detailed information about the relationship between #process,
   * #value, and #access.
   *
   * Because #access is set here, it affects display only and does not prevent
   * JavaScript or other untrusted code from submitting the form as though
   * access were enabled. The form processing functions for these elements
   * should not assume that the buttons can't be "clicked" just because they are
   * not displayed.
   *
   * @see \Drupal\file\Element\ManagedFile::processManagedFile()
   * @see \Drupal\Core\Form\FormBuilderInterface::doBuildForm()
   */
  public static function preRenderManagedFile($element) {
    // If we already have a file, we don't want to show the upload controls.
    if (!empty($element['#value']['media_uuid'])) {
      if (!$element['#multiple']) {
        $element['upload']['#access'] = FALSE;
        $element['upload_button']['#access'] = FALSE;
      }
    }
    // If we don't already have a file, there is nothing to remove.
    else {
      $element['remove_button']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Render API callback: Validates the managed_file element.
   */
  public static function validateManagedFile(&$element, FormStateInterface $form_state, &$complete_form) {
    $form_state->setValueForElement($element, ['uuids' => $element['uuids']['#value']]);
  }

  /**
   * Wraps the file usage service.
   *
   * @return \Drupal\file\FileUsage\FileUsageInterface
   */
  protected static function fileUsage() {
    return \Drupal::service('file.usage');
  }

}

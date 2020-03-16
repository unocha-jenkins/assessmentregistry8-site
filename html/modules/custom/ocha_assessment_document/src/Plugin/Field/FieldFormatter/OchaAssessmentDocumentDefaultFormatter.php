<?php

namespace Drupal\ocha_assessment_document\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\GenericFileFormatter;

/**
 * Plugin implementation of the 'ocha_assessment_document' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_assessment_document_default",
 *   label = @Translation("OCHA assessment document default formatter"),
 *   field_types = {
 *     "ocha_assessment_document"
 *   }
 * )
 */
class OchaAssessmentDocumentDefaultFormatter extends GenericFileFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($items->count()) {
      $files = $this->getEntitiesToView($items, $langcode);

      foreach ($items as $delta => $item) {
        // Skip missing files.
        if (!isset($files[$delta])) {
          continue;
        }

        // Build file URL.
        $file = $files[$delta];
        $file_url = file_create_url($file->getFileUri());

        // TODO: Check accessibility?
        $output = '';
        $output .= $item->accessibility;

        $output .= ' - ' . \Drupal::l($item->description, Url::fromUri($file_url, []));

        if (!empty($item->uri)) {
          $link_text = !empty($item->title) ? $item->title : $item->uri;
          $output .= ' - ' . \Drupal::l($link_text, Url::fromUri($item->uri, []));
        }

        $elements[$delta] = [
          '#markup' => $output,
        ];
      }
    }

    return $elements;
  }

}

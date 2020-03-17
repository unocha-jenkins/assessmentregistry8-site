<?php

namespace Drupal\ocha_assessment_document\Plugin\Field\FieldType;

use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Represents a configurable entity file field.
 */
class OchaAssessmentDocumentList extends FileFieldItemList {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->list as $item) {
      if ($item instanceof ComplexDataInterface || $item instanceof ListInterface) {
        if (!$item->isEmpty()) {
          return FALSE;
        }
      }
      // Other items are treated as empty if they have no value only.
      elseif ($item->getValue() !== NULL) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function filterEmptyItems() {
    $this->filter(function ($item) {
      return !$item->isEmpty();
    });
    return $this;
  }
}

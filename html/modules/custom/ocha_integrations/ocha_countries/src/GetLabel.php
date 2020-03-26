<?php

namespace Drupal\ocha_countries;

use Drupal\Core\TypedData\TypedData;

/**
 * A computed property for an average dice roll.
 */
class GetLabel extends TypedData {

  /**
   * Cached processed value.
   *
   * @var string|null
   */
  protected $processed = NULL;

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::getValue().
   */
  public function getValue($langcode = NULL) {
    if ($this->processed !== NULL) {
      return $this->processed;
    }

    $item = $this->getParent();
    $this->processed = $item->value;

    $term = ocha_countries_get_item($item->value);
    if ($term) {
      if (isset($term->label->default)) {
        $this->processed = $term->label->default;
      }
    }

    return $this->processed;
  }

  /**
   * Implements \Drupal\Core\TypedData\TypedDataInterface::setValue().
   */
  public function setValue($value, $notify = TRUE) {
    $this->processed = $value;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}

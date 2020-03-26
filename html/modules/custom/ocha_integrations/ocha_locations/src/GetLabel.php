<?php

namespace Drupal\ocha_locations;

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

    $this->processed = '';

    $item = $this->getParent();
    $term = ocha_locations_get_item($item->value);
    if ($term) {
      $this->processed = $term->name;
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

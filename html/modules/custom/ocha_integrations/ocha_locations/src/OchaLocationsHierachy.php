<?php

namespace Drupal\ocha_locations;

use Drupal\Core\TypedData\TypedData;

/**
 * Calculate hierachy.
 */
class OchaLocationsHierachy extends TypedData {

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
    $this->processed = $item->get('level0')->getValue();
    if (!empty($item->get('level1')->getValue())) {
      $this->processed .= '|' . $item->get('level1')->getValue();
    }
    if (!empty($item->get('level2')->getValue())) {
      $this->processed .= '|' . $item->get('level2')->getValue();
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

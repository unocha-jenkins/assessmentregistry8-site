<?php

namespace Drupal\ocha_map\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An ocha_map controller.
 */
class ochaMapController extends ControllerBase {

  /**
   * Returns a map.
   */
  public function map() {
    global $base_url;
    $src = $base_url . '/rest/assessments-map?items_per_page=8000';

    return array(
      '#theme' => 'ocha_map_map',
      '#base_url' => $base_url,
      '#src' => $src,
      '#component_url' => '/modules/custom/ocha_map/component/',
    );
  }

}

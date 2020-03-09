<?php
/**
 * @file
 * Contains \Drupal\ocha_integrations\Plugin\Field\FieldWidget\OchaIntegrationsOptionsButtonsWidget.
 */

namespace Drupal\ocha_integrations\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;

/**
 * Plugin implementation of the 'ocha_integrations' widget.
 *
 * @FieldWidget (
 *   id = "ocha_integrations_buttons",
 *   label = @Translation("OCHA buttons widget"),
 *   field_types = {
 *     "ocha_countries"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OchaIntegrationsOptionsButtonsWidget extends OptionsButtonsWidget {

}

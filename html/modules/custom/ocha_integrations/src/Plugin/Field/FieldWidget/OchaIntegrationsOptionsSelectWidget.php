<?php
/**
 * @file
 * Contains \Drupal\ocha_integrations\Plugin\Field\FieldWidget\OchaIntegrationsOptionsSelectWidget.
 */

namespace Drupal\ocha_integrations\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'ocha_integrations' widget.
 *
 * @FieldWidget (
 *   id = "ocha_integrations_select",
 *   label = @Translation("OCHA select widget"),
 *   field_types = {
 *     "ocha_countries"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OchaIntegrationsOptionsSelectWidget extends OptionsSelectWidget {

}

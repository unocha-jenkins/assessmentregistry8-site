<?php
/**
 * @file
 * Contains \Drupal\ocha_integrations\Plugin\Field\FieldFormatter\OchaIntegrationsDefaultFormatter.
 */

namespace Drupal\ocha_integrations\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;
/**
 * Plugin implementation of the 'ocha_integrations' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_integrations_default",
 *   label = @Translation("OCHA default formatter"),
 *   field_types = {
 *     "ocha_countries"
 *   }
 * )
 */
class OchaIntegrationsDefaultFormatter extends OptionsDefaultFormatter {

}

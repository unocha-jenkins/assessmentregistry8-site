<?php
/**
 * @file
 * Contains \Drupal\ocha_integrations\Plugin\Field\FieldFormatter\OchaIntegrationsKeyFormatter.
 */

namespace Drupal\ocha_integrations\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsKeyFormatter;

/**
 * Plugin implementation of the 'ocha_integrations' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_integrations_key",
 *   label = @Translation("OCHA key formatter"),
 *   field_types = {
 *     "ocha_countries"
 *   }
 * )
 */
class OchaIntegrationsKeyFormatter extends OptionsKeyFormatter {

}

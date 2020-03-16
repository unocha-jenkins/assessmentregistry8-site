<?php

namespace Drupal\ocha_integrations\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsKeyFormatter;

/**
 * Plugin implementation of the 'ocha_integrations' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_integrations_key",
 *   label = @Translation("OCHA key formatter"),
 *   field_types = {
 *     "ocha_countries",
 *     "ocha_disasters",
 *     "ocha_local_groups",
 *     "ocha_organizations",
 *     "ocha_themes",
 *     "ocha_population_type"
 *   }
 * )
 */
class OchaIntegrationsKeyFormatter extends OptionsKeyFormatter {

}

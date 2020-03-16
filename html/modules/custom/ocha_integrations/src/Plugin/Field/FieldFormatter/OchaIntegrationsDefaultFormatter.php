<?php

namespace Drupal\ocha_integrations\Plugin\Field\FieldFormatter;

use Drupal\options\Plugin\Field\FieldFormatter\OptionsDefaultFormatter;

/**
 * Plugin implementation of the 'ocha_integrations' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_integrations_default",
 *   label = @Translation("OCHA default formatter"),
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
class OchaIntegrationsDefaultFormatter extends OptionsDefaultFormatter {

}

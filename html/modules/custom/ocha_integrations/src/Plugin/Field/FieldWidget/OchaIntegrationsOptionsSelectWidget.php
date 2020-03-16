<?php

namespace Drupal\ocha_integrations\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;

/**
 * Plugin implementation of the 'ocha_integrations' widget.
 *
 * @FieldWidget (
 *   id = "ocha_integrations_select",
 *   label = @Translation("OCHA select widget"),
 *   field_types = {
 *     "ocha_countries",
 *     "ocha_disasters",
 *     "ocha_local_groups",
 *     "ocha_organizations",
 *     "ocha_themes",
 *     "ocha_population_type"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OchaIntegrationsOptionsSelectWidget extends OptionsSelectWidget {

}

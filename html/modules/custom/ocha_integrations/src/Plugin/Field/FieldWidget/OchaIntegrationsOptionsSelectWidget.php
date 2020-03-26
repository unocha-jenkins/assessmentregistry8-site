<?php

namespace Drupal\ocha_integrations\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;

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
 *     "ocha_population_type",
 *     "ocha_global_coordination_groups"
 *   },
 *   multiple_values = TRUE
 * )
 */
class OchaIntegrationsOptionsSelectWidget extends OptionsSelectWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $options = $element['#options'];
    uasort($options, 'ocha_integrations_order_options');
    $element['#options'] = $options;

    return $element;
  }

}

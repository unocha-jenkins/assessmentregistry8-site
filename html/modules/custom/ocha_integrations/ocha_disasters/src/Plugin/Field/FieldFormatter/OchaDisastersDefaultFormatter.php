<?php

namespace Drupal\ocha_disasters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\ocha_integrations\Plugin\Field\FieldFormatter\OchaIntegrationsDefaultFormatter;

/**
 * Plugin implementation of the 'ocha_disasters' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_disasters_default",
 *   label = @Translation("OCHA disasters formatter"),
 *   field_types = {
 *     "ocha_disasters"
 *   }
 * )
 */
class OchaDisastersDefaultFormatter extends OchaIntegrationsDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'output' => 'label',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['output'] = [
      '#title' => $this->t('Output'),
      '#type' => 'select',
      '#options' => [
        'label' => $this->t('Label'),
        'glide' => $this->t('Glide'),
      ],
      '#default_value' => $this->getSetting('output'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if ($items->count()) {
      $provider = $items->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getOptionsProvider('value', $items->getEntity());

      $options = OptGroup::flattenOptions($provider->getPossibleOptions());

      foreach ($items as $delta => $item) {
        $value = $item->value;

        $output = isset($options[$value]) ? $options[$value] : $value;
        if ($this->getSetting('output') != 'label') {
          // Get item data.
          $data = ocha_disasters_get_item($value, $langcode);
          switch ($this->getSetting('output')) {
            case 'glide':
              $output = $data->glide;
              break;

          }
        }

        $elements[$delta] = [
          '#markup' => $output,
          '#allowed_tags' => FieldFilteredMarkup::allowedTags(),
        ];
      }
    }

    return $elements;
  }

}

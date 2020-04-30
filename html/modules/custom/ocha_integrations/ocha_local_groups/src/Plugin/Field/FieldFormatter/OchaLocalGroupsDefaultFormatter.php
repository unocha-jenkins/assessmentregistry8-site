<?php

namespace Drupal\ocha_local_groups\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\ocha_integrations\Plugin\Field\FieldFormatter\OchaIntegrationsDefaultFormatter;

/**
 * Plugin implementation of the 'ocha_local_groups' formatter.
 *
 * @FieldFormatter (
 *   id = "ocha_local_groups_default",
 *   label = @Translation("OCHA local groups formatter"),
 *   field_types = {
 *     "ocha_local_groups"
 *   }
 * )
 */
class OchaLocalGroupsDefaultFormatter extends OchaIntegrationsDefaultFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'output' => 'label',
      'output_global_cluster' => 0,
      'output_lead_agencies' => 0,
      'output_partners' => 0,
      'output_activation_document' => 0,
      'output_operations' => 0,
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
        'extended' => $this->t('Extended'),
      ],
      '#default_value' => $this->getSetting('output'),
    ];

    $form['output_global_cluster'] = [
      '#title' => $this->t('Output global cluster'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('output_global_cluster'),
    ];

    $form['output_lead_agencies'] = [
      '#title' => $this->t('Output lead agencies'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('output_lead_agencies'),
    ];

    $form['output_partners'] = [
      '#title' => $this->t('Output partners'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('output_partners'),
    ];

    $form['output_activation_document'] = [
      '#title' => $this->t('Output activation document'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('output_activation_document'),
    ];

    $form['output_operations'] = [
      '#title' => $this->t('Output operations'),
      '#type' => 'select',
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#default_value' => $this->getSetting('output_operations'),
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
          $data = ocha_local_groups_get_item($value, $langcode);
          if (!isset($data->name)) {
            $output = $value;
          }
          else {

            switch ($this->getSetting('output')) {
              case 'extended':
                $output = $data->name;

                if ($this->getSetting('output_global_cluster')) {
                  if (!empty($data->global_cluster) && isset($data->global_cluster->label)) {
                    $output .= ' - ' . $data->global_cluster->label;
                  }
                }

                if ($this->getSetting('output_lead_agencies')) {
                  if (!empty($data->lead_agencies) && is_array($data->lead_agencies)) {
                    $agencies = [];
                    foreach ($data->lead_agencies as $lead_agency) {
                      $agencies[] = $lead_agency->label;
                    }
                    $output .= ' - ' . implode(', ', $agencies);
                  }
                }

                if ($this->getSetting('output_partners')) {
                  if (!empty($data->partners) && is_array($data->partners)) {
                    $partners = [];
                    foreach ($data->partners as $partner) {
                      $partners[] = $partner->label;
                    }
                    $output .= ' - ' . implode(', ', $partners);
                  }
                }

                if ($this->getSetting('output_activation_document')) {
                  if (!empty($data->activation_document) && isset($data->activation_document->label)) {
                    $output .= ' - ' . $data->activation_document->label;
                  }
                }

                if ($this->getSetting('output_operations')) {
                  if (!empty($data->operations) && is_array($data->operations)) {
                    $operations = [];
                    foreach ($data->operations as $operation) {
                      $operations[] = $operation->label;
                    }
                    $output .= ' - ' . implode(', ', $operations);
                  }
                }

                break;
            }
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

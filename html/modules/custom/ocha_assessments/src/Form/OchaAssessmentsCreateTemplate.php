<?php

namespace Drupal\ocha_assessments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Class OchaAssessmentsCreateTemplate.
 */
class OchaAssessmentsCreateTemplate extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ocha_assessments_create_template';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $controller = ocha_countries_get_controller();
    $countries = $controller->getAllowedValues();
    $form['country'] = [
      '#type' => 'select',
      '#options' => $countries,
      '#title' => $this->t('Country'),
      '#description' => $this->t('Country'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = ocha_countries_get_item($form_state->getValue('country'));

    // Copy static template.
    $source = drupal_get_path('module', 'ocha_assessments') . '/bulk_template.xlsx';
    $destination = 'public://template_' . $country->iso3 . '_' . date('Ymdhni') . '.xlsx';

    $filename = drupal_realpath($source);

    $reader = new Xlsx();
    $reader->setReadDataOnly(FALSE);
    $spreadsheet = $reader->load($filename);

    // Add population type.
    $worksheet = $spreadsheet->getSheetByName('Population Types');
    $controller = ocha_population_type_get_controller();
    $options = $controller->getAllowedValues();
    $options = array_chunk($options, 1);
    $worksheet->fromArray($options, NULL, 'A1');
    // $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Set validators, they tend to disappear.

    // Protect headers.

    // Hide reference data sheets.

    // Set focus to main sheet.
    $worksheet = $spreadsheet->getSheetByName('Assessments');

    // Save
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save(drupal_realpath($destination));

    // Stream to browser?
    dpm($destination);
  }

}

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
    $countries = ocha_locations_allowed_values_top_level();
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
    $country = ocha_locations_get_item($form_state->getValue('country'));

    // Set paths.
    $destination = 'public://template_' . $country->iso3 . '_' . date('Ymdhni') . '.xlsx';
    $source = drupal_get_path('module', 'ocha_assessments') . '/bulk_template.xlsx';
    $filename = drupal_realpath($source);

    $reader = new Xlsx();
    $reader->setReadDataOnly(FALSE);
    $spreadsheet = $reader->load($filename);

    // Hide Accessibility.
    $worksheet = $spreadsheet->getSheetByName('Accessibility');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Hide Status.
    $worksheet = $spreadsheet->getSheetByName('Status');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Add global clusters.
    $worksheet = $spreadsheet->getSheetByName('Clusters');
    $controller = ocha_global_coordination_groups_get_controller();
    $options = $controller->getAllowedValues();
    $options = array_chunk($options, 1);
    $worksheet->fromArray($options, NULL, 'A1');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Organizations.
    $worksheet = $spreadsheet->getSheetByName('Organizations');
    $controller = ocha_organizations_get_controller();
    $options = $controller->getAllowedValues();
    $options = array_chunk($options, 1);
    $worksheet->fromArray($options, NULL, 'A1');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Admin levels.
    $worksheet = $spreadsheet->getSheetByName('Admin1');
    $options = ocha_locations_children_to_options($country);
    $options = array_chunk($options, 1);
    $worksheet->fromArray($options, NULL, 'A1');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Add population type.
    $worksheet = $spreadsheet->getSheetByName('PopulationTypes');
    $controller = ocha_population_type_get_controller();
    $options = $controller->getAllowedValues();
    $options = array_chunk($options, 1);
    $worksheet->fromArray($options, NULL, 'A1');
    $worksheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    // Set validators for clusters.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('B9')->getDataValidation();
    $validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
    $validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(TRUE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Clusters!$A$1:$A$9999');

    // Set validators for organisations.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('C9')->getDataValidation();
    $validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
    $validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(TRUE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Organizations!$A$1:$A$9999');

    $worksheet->getCell('D9')->setDataValidation(clone $validation);

    // Set validators for status.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('I9')->getDataValidation();
    $validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
    $validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(TRUE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Status!$A$1:$A$9999');

    // Set validators for population types.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('L9')->getDataValidation();
    $validation->setType( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST );
    $validation->setErrorStyle( \PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION );
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(TRUE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('PopulationTypes!$A$1:$A$9999');

    // Set title.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $worksheet->getCell('C2')->setValue('Assessment Registry – ' . $country->label->default);

    // Protect headers.

    // Hide reference data sheets.

    // Set focus to main sheet.
    $spreadsheet->setActiveSheetIndexByName('Assessments');

    // Save
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save(drupal_realpath($destination));

    // Stream to browser?
    dpm('<a href="' . file_create_url($destination) . '">Download</a>');
  }

}

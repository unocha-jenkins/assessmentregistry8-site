<?php

namespace Drupal\ocha_assessments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Class OchaAssessmentsCreateTemplate.
 */
class OchaAssessmentsCreateTemplate extends FormBase {

  use StringTranslationTrait;

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
    $form['country'] = [
      '#type' => 'select',
      '#options' => ocha_locations_allowed_values_top_level(),
      '#title' => $this->t('Country'),
      '#description' => $this->t('Country'),
      '#required' => TRUE,
    ];

    $form['operation'] = [
      '#type' => 'select',
      '#options' => ocha_local_groups_get_operations(),
      '#title' => $this->t('Limit local cluster by operation'),
      '#required' => FALSE,
      '#empty_option' => $this->t('- None -'),
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
    $operation = $form_state->getValue('operation');

    // Set paths.
    $destination = 'public://template_' . $country->iso3 . '_' . date('Ymdhni') . '.xlsx';
    $source = drupal_get_path('module', 'ocha_assessments') . '/bulk_template.xlsx';
    $filename = drupal_realpath($source);

    $reader = new Xlsx();
    $reader->setReadDataOnly(FALSE);
    $spreadsheet = $reader->load($filename);

    // Hide Accessibility.
    $worksheet = $spreadsheet->getSheetByName('Accessibility');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Hide Status.
    $worksheet = $spreadsheet->getSheetByName('Status');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Hide Units.
    $worksheet = $spreadsheet->getSheetByName('Units');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Hide Collection method.
    $worksheet = $spreadsheet->getSheetByName('CollectionMethod');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Add local clusters.
    $worksheet = $spreadsheet->getSheetByName('Clusters');
    $controller = ocha_local_groups_get_controller();
    $options = $controller->getAllowedValues();

    // Limit by operation.
    if (!empty($operation)) {
      foreach ($options as $key => $value) {
        if (!strpos($value, '(' . $operation . ')')) {
          unset($options[$key]);
        }
        else {
          // Remove operation name.
          $options[$key] = str_replace(' (' . $operation . ')', '', $value);
        }
      }
    }
    $worksheet->fromArray($this->optionsToXlsArray($options), NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Organizations.
    $worksheet = $spreadsheet->getSheetByName('Organizations');
    $controller = ocha_organizations_get_controller();
    $options = $controller->getAllowedValues();
    $worksheet->fromArray($this->optionsToXlsArray($options), NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Admin levels.
    $admin_levels1 = [];
    $admin_levels2 = [];
    $admin_levels3 = [];
    foreach ($country->children as $l1) {
      $admin_levels1[] = [$this->makeLabel($l1->name, $l1->id)];
      if (!empty($l1->children)) {
        foreach ($l1->children as $l2) {
          $admin_levels2[] = [
            $this->makeLabel($l1->name, $l1->id),
            $this->makeLabel($l2->name, $l2->id),
          ];
          if (!empty($l2->children)) {
            foreach ($l2->children as $l3) {
              $admin_levels3[] = [
                $this->makeLabel($l1->name, $l1->id),
                $this->makeLabel($l2->name, $l2->id),
                $this->makeLabel($l3->name, $l3->id),
              ];
            }
          }
        }
      }
    }
    $worksheet = $spreadsheet->getSheetByName('AdminLevel1');
    $worksheet->fromArray($admin_levels1, NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    $worksheet = $spreadsheet->getSheetByName('AdminLevel2');
    $worksheet->fromArray($admin_levels2, NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    $worksheet = $spreadsheet->getSheetByName('AdminLevel3');
    $worksheet->fromArray($admin_levels3, NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Add population type.
    $worksheet = $spreadsheet->getSheetByName('PopulationTypes');
    $controller = ocha_population_type_get_controller();
    $options = $controller->getAllowedValues();
    $worksheet->fromArray($this->optionsToXlsArray($options), NULL, 'A1');
    $worksheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);
    $worksheet->getProtection()->setSheet(TRUE);

    // Set validators for clusters.
    $worksheet = $spreadsheet->getSheetByName('Clusters');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('B9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Clusters!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('B9:B999', $validation);

    // Set validators for organizations.
    $worksheet = $spreadsheet->getSheetByName('Organizations');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('C9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Organizations!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('C9:D999', $validation);

    // Set validators for admin level 1.
    $worksheet = $spreadsheet->getSheetByName('AdminLevel1');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('E9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('AdminLevel1!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('E9:E999', $validation);

    // Set validators for admin level 2.
    $worksheet = $spreadsheet->getSheetByName('AdminLevel2');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('F9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('OFFSET(AdminLevel2!A1,MATCH(E9,AdminLevel2!$A$1:$A$9999,0)-1,1,COUNTIF(AdminLevel2!$A$1:$A$9999,E9),1)');
    $worksheet->setDataValidation('F9:F999', $validation);

    // Set validators for admin level 3.
    $worksheet = $spreadsheet->getSheetByName('AdminLevel3');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('G9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('OFFSET(AdminLevel3!B1,MATCH(F9,AdminLevel3!$B$1:$B$9999,0)-1,1,COUNTIF(AdminLevel3!$B$1:$B$9999,F9),1)');
    $worksheet->setDataValidation('G9:G999', $validation);

    // Set validators for units of measurement.
    $worksheet = $spreadsheet->getSheetByName('Units');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('M9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Units!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('M9:M999', $validation);

    // Set validators for collection method.
    $worksheet = $spreadsheet->getSheetByName('CollectionMethod');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('N9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('CollectionMethod!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('N9:N999', $validation);

    // Set validators for status.
    $worksheet = $spreadsheet->getSheetByName('Status');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('I9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Status!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('I9:I999', $validation);

    // Set validators for population types.
    $worksheet = $spreadsheet->getSheetByName('PopulationTypes');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('L9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('PopulationTypes!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('L9:L999', $validation);

    // Set validators for accessibility.
    $worksheet = $spreadsheet->getSheetByName('Accessibility');
    $highest_row = $worksheet->getHighestRow();
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $validation = $worksheet->getCell('O9')->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
    $validation->setAllowBlank(TRUE);
    $validation->setShowInputMessage(FALSE);
    $validation->setShowErrorMessage(TRUE);
    $validation->setShowDropDown(TRUE);
    $validation->setErrorTitle('Input error');
    $validation->setError('Value is not in list.');
    $validation->setPromptTitle('Pick from list');
    $validation->setPrompt('Please pick a value from the drop-down list.');
    $validation->setFormula1('Accessibility!$A$1:$A$' . $highest_row);
    $worksheet->setDataValidation('O9:O999', $validation);
    $worksheet->setDataValidation('Q9:Q999', clone $validation);
    $worksheet->setDataValidation('S9:S999', clone $validation);

    // Set title.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $worksheet->getCell('C2')->setValue('Assessment Registry – ' . $country->name);

    // Protect headers.
    $worksheet = $spreadsheet->getSheetByName('Assessments');
    $worksheet->getProtection()->setSheet(TRUE);
    $spreadsheet->getDefaultStyle()->getProtection()->setLocked(TRUE);
    $worksheet->getStyle('A9:X999')->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

    // Set focus to main sheet.
    $spreadsheet->setActiveSheetIndexByName('Assessments');

    // Set meta data
    $spreadsheet->getProperties()
      ->setCreator('OCHA')
      ->setLastModifiedBy('OCHA')
      ->setTitle('Assessment Registry – ' . $country->name)
      ->setSubject('Assessment Registry – ' . $country->name)
      ->setKeywords('OCHA AR ' . $country->iso3);

    // Save.
    $writer = new XlsxWriter($spreadsheet);
    $writer->save(drupal_realpath($destination));

    // Stream to browser?
    drupal_set_message($this->t('<a href="@url">Download template</a>.', [
      '@url' => file_create_url($destination),
    ]), 'succes');
  }

  /**
   * Make label containing Id.
   */
  protected function makeLabel($label, $id) {
    return $label . ' [' . $id . ']';
  }

  /**
   * Convert options to array for xlsx.
   */
  protected function optionsToXlsArray($options) {
    $results = [];

    foreach ($options as $id => $label) {
      $results[] = [$label . ' [' . $id . ']'];
    }

    return $results;
  }

}

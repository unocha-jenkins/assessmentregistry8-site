<?php

namespace Drupal\ocha_assessments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OchaAssessmentsBulkImport.
 */
class OchaAssessmentsBulkImport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ocha_assessments_bulk_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['xlsx_file'] = [
      '#type' => 'file',
      '#title' => $this->t('Xlsx file'),
      '#description' => $this->t('Excel file containing assessments to import'),
      '#weight' => '0',
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
    $all_files = $this->getRequest()->files->get('files', []);
    if (!empty($all_files['xlsx_file'])) {
      $file_upload = $all_files['xlsx_file'];
      if ($file_upload->isValid()) {
        $form_state->setValue('xlsx_file', $file_upload->getRealPath());
        return;
      }
    }

    $form_state->setErrorByName('xlsx_file', $this->t('The file could not be uploaded.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $validators = ['file_validate_extensions' => ['xlsx']];
    $file = file_save_upload('xlsx_file', $validators, FALSE, 0);
    if (!$file) {
      return;
    }

    $filename = drupal_realpath($file->destination);
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filename);
    $reader->setReadDataOnly(true);
    $reader->load($filename);

    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $reader->setReadDataOnly(true);
    $reader->setLoadSheetsOnly(['Assessments']);
    $spreadsheet = $reader->load($filename);

    $headers = [];
    $header_lowercase = [];

    $worksheet = $spreadsheet->getActiveSheet();
    foreach ($worksheet->getRowIterator() as $row) {
      // Skip first 8 rows.
      if ($row->getRowIndex() < 8) {
        continue;
      }

      if ($row->getRowIndex() == 8) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(TRUE);
        foreach ($cellIterator as $cell) {
          $header[$cell->getColumn()] = $cell->getValue();
          $header[$cell->getColumn()] = $cell->getValue();
        }

        $header_lowercase = array_map('strtolower', $header);
        $header_lowercase = array_map('trim', $header_lowercase);
        continue;
      }

      $data = [
        '_row' => $row->getRowIndex(),
      ];

      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(TRUE);
      foreach ($cellIterator as $cell) {
        $data[$header_lowercase[$cell->getColumn()]] = $cell->getValue();
      }

      dpm($data);
    }
  }

}

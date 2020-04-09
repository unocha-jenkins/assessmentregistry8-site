<?php

namespace Drupal\ocha_assessments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

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
    $reader = IOFactory::createReaderForFile($filename);
    $reader->setReadDataOnly(TRUE);
    $reader->load($filename);

    $reader = new Xlsx();
    $reader->setReadDataOnly(TRUE);
    $reader->setLoadSheetsOnly(['Assessments']);
    $spreadsheet = $reader->load($filename);

    $header = [];
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

      $this->createDocument($data);
    }
  }

  /**
   * Create new assessment document.
   */
  protected function createDocument($item) {
    // Trim all fields.
    $item = array_map('trim', $item);

    // Create node object.
    $data = [
      'type' => 'assessment',
      'title' => $item['title'],
      'field_local_groups' => [],
      'field_organizations' => [],
      'field_asst_organizations' => [],
      'field_locations' => [],
      'field_population_types' => [],
      'field_themes' => [],
      'field_units_of_measurement' => [],
      'field_disasters' => [],
      'field_assessment_data' => [],
      'field_assessment_report' => [],
      'field_assessment_questionnaire' => [],
    ];

    // Local group aka Cluster(s)/Sector(s).
    if (isset($item['clusters']) && !empty($item['clusters'])) {
      // Split and trim.
      $data = array_map('trim', explode(',', $item['clusters']));
      foreach ($data as $input) {
        // TODO: Limit cluster to country or location.
      }
    }

    // Leading/Coordinating Organization(s).
    if (isset($item['agency']) && !empty($item['agency'])) {
      // Split and trim.
      $data = array_map('trim', explode(',', $item['agency']));
      foreach ($data as $input) {
        // TODO: Limit cluster to country or location.
      }
    }

    // Participating Organization(s).
    if (isset($item['partners']) && !empty($item['partners'])) {
      // Split and trim.
      $data = array_map('trim', explode(',', $item['partners']));
      foreach ($data as $input) {
      }
    }

    // Location(s).
    if (isset($item['admin 1']) && !empty($item['admin 1'])) {
      // Split and trim.
      $data = array_map('trim', explode(',', $item['partners']));
      foreach ($data as $input) {
        // Reverse lookup from admin 4 to admin 1.
      }
    }

    // Population Type(s).
    if (isset($item['types']) && !empty($item['types'])) {
      // Split and trim.
      $data = array_map('trim', explode(',', $item['types']));
      foreach ($data as $input) {
      }
    }

    // Unit(s) of Measurement.
    if (isset($item['units']) && !empty($item['units'])) {
      // Single value.
    }

    // Operation/Country.
    if (isset($item['country']) && !empty($item['country'])) {
      // TODO: Read from form input.
    }

    // Other location.
    if (isset($item['other_location']) && !empty($item['other_location'])) {
      $data['field_other_location'] = $item['other_location'];
    }

    // Status.
    if (isset($item['status']) && !empty($item['status'])) {
      $data['field_status'] = $item['status'];
    }

    // Assessment Date(s).
    if (isset($item['start']) && !empty($item['start'])) {
      $data['field_ass_date'][0] = [
        'value' => substr($item['start']->value, 0, 10),
      ];

      // End date.
      if (isset($item['end']) && !empty($item['end'])) {
        $data['field_ass_date'][0]['end_value'] = substr($item['end']->value2, 0, 10);
      }
    }

    if (isset($item['data availability']) && !empty($item['data availability'])) {
      $data['field_assessment_data'][] = ocha_assessments_create_document($item['data availability'], $item['data url']);
    }

    if (isset($item['report availability']) && !empty($item['report availability'])) {
      $data['field_assessment_report'][] = ocha_assessments_create_document($item['report availability'], $item['report url']);
    }

    if (isset($item['questionnaire availability']) && !empty($item['questionnaire availability'])) {
      $data['field_assessment_questionnaire'][] = ocha_assessments_create_document($item['questionnaire availability'], $item['questionnaire url']);
    }

    $node = Node::create($data);
    $node->save();
  }

}

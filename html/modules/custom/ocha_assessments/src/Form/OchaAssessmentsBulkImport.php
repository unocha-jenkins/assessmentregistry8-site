<?php

namespace Drupal\ocha_assessments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
    ];

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
        'country' => $form_state->getValue('country'),
      ];

      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(TRUE);
      foreach ($cellIterator as $cell) {
        $data[$header_lowercase[$cell->getColumn()]] = $cell->getValue();
      }

      if (isset($data['title']) && !empty($data['title'])) {
        $this->createDocument($data);
      }
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
      $values = array_map('trim', explode(',', $item['clusters']));
      foreach ($values as $input) {
        $data['field_local_groups'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }

    // Leading/Coordinating Organization(s).
    if (isset($item['agency']) && !empty($item['agency'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['agency']));
      foreach ($values as $input) {
        $data['field_organizations'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }

    // Participating Organization(s).
    if (isset($item['partners']) && !empty($item['partners'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['partners']));
      foreach ($values as $input) {
        $data['field_asst_organizations'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }

    // Location(s).
    if (isset($item['admin 3']) && !empty($item['admin 3'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['admin 3']));
      foreach ($values as $input) {
        $data['field_locations'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }
    elseif (isset($item['admin 2']) && !empty($item['admin 2'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['admin 2']));
      foreach ($values as $input) {
        $data['field_locations'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }
    elseif (isset($item['admin 1']) && !empty($item['admin 1'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['admin 1']));
      foreach ($values as $input) {
        $data['field_locations'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }

    // Other location.
    if (isset($item['admin 4']) && !empty($item['admin 4'])) {
      // TODO: Check length.
      $data['field_other_location'] = substr($item['admin 4'], 0, 255);
    }

    // Population Type(s).
    if (isset($item['types']) && !empty($item['types'])) {
      // Split and trim.
      $values = array_map('trim', explode(',', $item['types']));
      foreach ($values as $input) {
        $data['field_population_types'][] = [
          'value' => $this->extractIdFromInput($input),
        ];
      }
    }

    // Unit(s) of Measurement.
    if (isset($item['units']) && !empty($item['units'])) {
      $data['field_units_of_measurement'][] = [
        'value' => $item['units'],
      ];
    }

    // Collection Method(s).
    if (isset($item['type']) && !empty($item['type'])) {
      $data['field_collection_methods'][] = [
        'value' => $item['type'],
      ];
    }

    // Operation/Country.
    if (isset($item['country']) && !empty($item['country'])) {
      $data['field_countries'][] = [
        'value' => $item['country'],
      ];
    }

    // Status.
    if (isset($item['status']) && !empty($item['status'])) {
      $data['field_status'] = $item['status'];
    }

    // Assessment Date(s).
    if (isset($item['start']) && !empty($item['start'])) {
      if (strpos($item['start'], '-')) {
        $data['field_ass_date'][0] = [
          'value' => $item['start'],
        ];
      }
      else {
        $data['field_ass_date'][0] = [
          'value' => date('Y-m-d', Date::excelToTimestamp($item['start'])),
        ];
      }

      // End date.
      if (isset($item['end']) && !empty($item['end'])) {
        if (strpos($item['end'], '-')) {
          $data['field_ass_date'][0]['end_value'] = $item['end'];
        }
        else {
          $data['field_ass_date'][0]['end_value'] = date('Y-m-d', Date::excelToTimestamp($item['end']));
        }
      }
    }

    $instructions = isset($item['instructions']) ? $item['instructions'] : '';
    if (isset($item['data availability']) && !empty($item['data availability'])) {
      $data['field_assessment_data'][] = ocha_assessments_create_document($item['data availability'], $item['data url'], $instructions);
    }

    if (isset($item['report availability']) && !empty($item['report availability'])) {
      $data['field_assessment_report'][] = ocha_assessments_create_document($item['report availability'], $item['report url'], $instructions);
    }

    if (isset($item['questionnaire availability']) && !empty($item['questionnaire availability'])) {
      $data['field_assessment_questionnaire'][] = ocha_assessments_create_document($item['questionnaire availability'], $item['questionnaire url'], $instructions);
    }

    $node = Node::create($data);
    $node->save();
  }

  /**
   * Extract Id from input string.
   */
  protected function extractIdFromInput($input) {
    $pos = strrpos($input, '[');
    return substr($input, $pos + 1, -1);
  }

}

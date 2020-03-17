<?php

namespace Drupal\ocha_assessment_document\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\link\LinkItemInterface;

/**
 * Plugin implementation of the 'ocha_assessment_document' field type.
 *
 * @FieldType (
 *   id = "ocha_assessment_document",
 *   label = @Translation("OCHA assessment document"),
 *   description = @Translation("OCHA assessment document."),
 *   category = @Translation("OCHA"),
 *   default_widget = "ocha_assessment_document_widget",
 *   default_formatter = "ocha_assessment_document_default",
 *   list_class = "\Drupal\ocha_assessment_document\Plugin\Field\FieldType\OchaAssessmentDocumentList",
 *   constraints = {"ReferenceAccess" = {}, "FileValidation" = {}}
 * )
 */
class OchaAssessmentDocument extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'title' => DRUPAL_OPTIONAL,
      'link_type' => LinkItemInterface::LINK_GENERIC,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->accessibility == 'Publicly Available') {
      if ($this->target_id !== NULL) {
        return FALSE;
      }
      if ($this->entity) {
        return FALSE;
      }
      if ($this->uri !== NULL) {
        return FALSE;
      }
    }

    if ($this->accessibility == 'Available on Request') {
      if ($this->instructions !== NULL) {
        return FALSE;
      }
    }

    if ($this->accessibility == 'Not Available') {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['accessibility'] = DataDefinition::create('string')
      ->setLabel(t('Text Value'));

    $properties['uri'] = DataDefinition::create('string')
      ->setLabel(t('URI'));

    $properties['title'] = DataDefinition::create('string')
      ->setLabel(t('Link text'));

    $properties['instructions'] = DataDefinition::create('string')
      ->setLabel(t('Instructions'))
      ->setSetting('case_sensitive', FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['accessibility'] = [
      'description' => 'The link text.',
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['uri'] = [
      'description' => 'The URI of the link.',
      'type' => 'varchar',
      'length' => 2048,
    ];

    $schema['columns']['title'] = [
      'description' => 'The link text.',
      'type' => 'varchar',
      'length' => 255,
    ];

    $schema['columns']['instructions'] = [
      'description' => 'Instructions text.',
      'type' => 'text',
      'size' => 'big',
    ];

    return $schema;
  }

}

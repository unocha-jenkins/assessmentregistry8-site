<?php

namespace Drupal\ocha_persons\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Person entity.
 *
 * @ingroup ocha_persons
 *
 * @ContentEntityType(
 *   id = "person_entity",
 *   label = @Translation("Person"),
 *   handlers = {
 *     "storage" = "Drupal\ocha_persons\PersonEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ocha_persons\PersonEntityListBuilder",
 *     "views_data" = "Drupal\ocha_persons\Entity\PersonEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\ocha_persons\Form\PersonEntityForm",
 *       "add" = "Drupal\ocha_persons\Form\PersonEntityForm",
 *       "edit" = "Drupal\ocha_persons\Form\PersonEntityForm",
 *       "delete" = "Drupal\ocha_persons\Form\PersonEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\ocha_persons\PersonEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ocha_persons\PersonEntityAccessControlHandler",
 *   },
 *   base_table = "person_entity",
 *   revision_table = "person_entity_revision",
 *   revision_data_table = "person_entity_field_revision",
 *   translatable = FALSE,
 *   admin_permission = "administer person entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/person/{person_entity}",
 *     "add-form" = "/person/add",
 *     "edit-form" = "/person/{person_entity}/edit",
 *     "delete-form" = "/person/{person_entity}/delete",
 *     "version-history" = "/person/{person_entity}/revisions",
 *     "revision" = "/person/{person_entity}/revisions/{person_entity_revision}/view",
 *     "revision_revert" = "/person/{person_entity}/revisions/{person_entity_revision}/revert",
 *     "revision_delete" = "/person/{person_entity}/revisions/{person_entity_revision}/delete",
 *     "collection" = "/admin/content/person",
 *   },
 *   field_ui_base_route = "person_entity.settings"
 * )
 */
class PersonEntity extends EditorialContentEntityBase implements PersonEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the person.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Person entity is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}

<?php

namespace Drupal\ocha_persons;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Person entity entities.
 *
 * @ingroup ocha_persons
 */
class PersonEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['phone'] = $this->t('Phone');
    $header['email'] = $this->t('Email');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\ocha_persons\Entity\PersonEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.person_entity.edit_form',
      ['person_entity' => $entity->id()]
    );
    $row['phone'] = $entity->get('field_phone')->first()->value ?? '';
    $row['email'] = $entity->get('field_email')->first()->value ?? '';
    return $row + parent::buildRow($entity);
  }

}

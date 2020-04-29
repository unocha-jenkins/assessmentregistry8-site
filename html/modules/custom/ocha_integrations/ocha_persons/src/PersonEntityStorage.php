<?php

namespace Drupal\ocha_persons;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\ocha_persons\Entity\PersonEntityInterface;

/**
 * Defines the storage handler class for Person entity entities.
 *
 * This extends the base storage class, adding required special handling for
 * Person entity entities.
 *
 * @ingroup ocha_persons
 */
class PersonEntityStorage extends SqlContentEntityStorage implements PersonEntityStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(PersonEntityInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {person_entity_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {person_entity_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

}

<?php

namespace Drupal\ocha_persons;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface PersonEntityStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Person entity revision IDs for a specific Person entity.
   *
   * @param \Drupal\ocha_persons\Entity\PersonEntityInterface $entity
   *   The Person entity entity.
   *
   * @return int[]
   *   Person entity revision IDs (in ascending order).
   */
  public function revisionIds(PersonEntityInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Person entity author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Person entity revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

}

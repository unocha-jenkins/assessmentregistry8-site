<?php

namespace Drupal\ocha_persons\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Person entity entities.
 *
 * @ingroup ocha_persons
 */
interface PersonEntityInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Person entity name.
   *
   * @return string
   *   Name of the Person entity.
   */
  public function getName();

  /**
   * Sets the Person entity name.
   *
   * @param string $name
   *   The Person entity name.
   *
   * @return \Drupal\ocha_persons\Entity\PersonEntityInterface
   *   The called Person entity entity.
   */
  public function setName($name);

  /**
   * Gets the Person entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Person entity.
   */
  public function getCreatedTime();

  /**
   * Sets the Person entity creation timestamp.
   *
   * @param int $timestamp
   *   The Person entity creation timestamp.
   *
   * @return \Drupal\ocha_persons\Entity\PersonEntityInterface
   *   The called Person entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Person entity revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Person entity revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\ocha_persons\Entity\PersonEntityInterface
   *   The called Person entity entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Person entity revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Person entity revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\ocha_persons\Entity\PersonEntityInterface
   *   The called Person entity entity.
   */
  public function setRevisionUserId($uid);

}

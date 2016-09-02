<?php
/**
 * @file
 * Drupal\push_notifications\PushNotificationInterface.
 */
namespace Drupal\push_notifications;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a push_notification entity.
 *
 * @ingroup push_notifications
 */
interface PushNotificationInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Returns the entity push notification token id for this push notification.
   *
   * @return integer
   *   The token id related for this entity.
   */
  public function getTokenId();

  /**
   * Returns the entity push notification title.
   *
   * @return string
   *   The title.
   */
  public function getTitle();

  /**
   * Returns the entity push notification message.
   *
   * @return string
   *   The push notification message.
   */
  public function getMessage();

  /**
   * Returns the entity push notification payload.
   *
   * @return string
   *   The payload.
   */
//  public function getPayload();

  /**
   * Returns the entity's created timestamp.
   *
   * @return string
   *   The created timestamp for this entity.
   */
  public function getCreated();

  /**
   * Returns the push_notification pushed status indicator.
   *
   * @return bool
   *   TRUE if the push_notification is pushed.
   */
  public function isPushed();

  /**
   * Sets the pushed status of a push_notification.
   *
   * @todo: add documentation
   */
  public function setPushed($pushed);

}
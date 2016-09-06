<?php
/**
 * @file
 * Contains Drupal\push_notifications\Entity\Controller\PushNotificationListBuilder
 */

namespace Drupal\push_notifications\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a list controller for the push_notification entity.
 *
 * @ingroup push_notification
 */
class PushNotificationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Add the table header.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('List of all push notifications in the database.'),
    );

    $build['table'] = parent::render();
    $build['table']['table']['#empty'] = $this->t('There are no push notifications.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // @todo: Create route to send the push notification through a link

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['user_id'] = $this->t('Author');
    $header['title'] = $this->t('Title');
    $header['message'] = $this->t('Message');
    $header['created'] = $this->t('Created');
    $header['pushed'] = $this->t('Pushed');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\push_notifications\Entity\PushNotificationsToken */
    $row['id'] = $entity->id();
    $row['user_id']['data'] = array(
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    );
    $row['title'] = Link::fromTextAndUrl(
      $entity->getTitle(),
      Url::fromRoute('entity.push_notification.canonical', array('push_notification' => $entity->id()))
    );
    $row['message'] = $entity->getMessage();
    $row['created'] = $entity->getCreatedTime();
    $row['pushed'] = $entity->isPushed() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
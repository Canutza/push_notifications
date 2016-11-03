<?php

/**
 * @file
 * Contains Drupal\push_notifications\PushNotificationsTokenQuery.
 */

namespace Drupal\push_notifications;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PushNotificationsTokenQuery {

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entity_query;

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * PushNotificationsTokenQuery constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   */
  public function __construct(QueryFactory $entity_query, EntityManagerInterface $entityManager) {
    $this->entity_query = $entity_query;
    $this->entityManager = $entityManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')
    );
  }

  /**
   * Get the push notification tokens by user ID.
   *
   * @param array $uids
   *   User IDs.
   * @return array|null
   */
  public function getTokensByUid($uids) {
    if (!is_array($uids)) {
      return NULL;
    }

    $push_notifications_token_storage = $this->entityManager->getStorage('push_notifications_token');
    $push_notifications_token = $push_notifications_token_storage->loadByProperties(array('uid' => $uids));
    $tokens = array();

    foreach ($push_notifications_token as $pid => $push_notification_token) {
      array_push($tokens, $push_notification_token->getToken());
    }

    return $tokens;
  }

  /**
   * Get the push notification tokens by network.
   *
   * @param array $networks
   *   Push Networks.
   * @return array|null
   */
  public function getTokensByNetwork($networks) {
    if (!is_array($networks)) {
      return NULL;
    }

    $push_notifications_token_storage = $this->entityManager->getStorage('push_notifications_token');
    $push_notifications_token = $push_notifications_token_storage->loadByProperties(array('network' => $networks));

    // Retrieve all tokens into array.
    $tokens = array();
    foreach ($push_notifications_token as $pid => $push_notification_token) {
      array_push($tokens, $push_notification_token->getToken());
    }

    return $tokens;
  }

  /**
   *
   *
   * @param $token
   * @param $uid
   * @return array
   */
  public function checkTokenByUid($token, $uid) {
    $push_notifications_token_storage = $this->entityManager->getStorage('push_notifications_token');
    $push_notifications_tokens = $push_notifications_token_storage->loadByProperties(array('uid' => $uid, 'token' => $token));

    return $push_notifications_tokens;
  }

  /**
   * Get all the push notification tokens.
   *
   * @return array
   */
  public function getAllTokens() {
    $push_notifications_token_storage = $this->entityManager->getStorage('push_notifications_token');
    $push_notifications_token = $push_notifications_token_storage->loadMultiple();

    // Retrieve all tokens into array.
    $tokens = array();
    foreach ($push_notifications_token as $pid => $push_notification_token) {
      array_push($tokens, $push_notification_token->getToken());
    }

    return $tokens;
  }

}
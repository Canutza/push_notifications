<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsMessageSenderList.
 */

namespace Drupal\push_notifications;

/**
 * Send a simple message alert to a list of tokens.
 * Use this if you want to preopulate the list of tokens.
 */
class PushNotificationsMessageSenderList extends PushNotificationsMessageSenderBase {

  /**
   * @var array $tokens
   *   Recipient tokens.
   */
  protected $tokens;

  /**
   * Constructor.
   *
   * @param \Drupal\push_notifications\PushNotificationsDispatcher $dispatcher
   *   Alert Dispatcher.
   */
  public function __construct(PushNotificationsDispatcher $dispatcher) {
    $this->dispatcher = $dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function setTokens($tokens) {
    $this->tokens = $tokens;
  }

  /**
   * Tokens don't need to be generated, but function
   * required for abstract base class.
   */
  public function generateTokens() {}

}
<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsMessageSenderBase.
 */

namespace Drupal\push_notifications;

use Drupal\Component\Utility\Unicode;

/**
 * Handles sending of alerts.
 */
abstract class PushNotificationsMessageSenderBase {

  /**
   * @var string $message
   *   The message that will be used in the payload.
   */
  protected $message;

  /**
   * @var array $tokens
   *   List of user tokens.
   */
  protected $tokens = array();

  /**
   * @var object $dispatcher
   *   Alert Dispatcher.
   */
  protected $dispatcher;

  /**
   * @var array $networks
   *   Target Networks.
   */
  protected $networks;

  /**
   * @var array $results
   *   Result data.
   */
  protected $results;

  /**
   * Constructor.
   */
  public function __construct() {}

  /**
   * Setter functions for networks.
   *
   * @param array $networks
   *   Target networks.
   * @throws \Exception
   *   Passed networks need to be array and valid networks.
   */
  public function setNetworks($networks) {
    // Validate networks.
    $valid_networks = push_notifications_get_networks();
    if (empty($networks) || !empty(array_diff($networks, $valid_networks))) {
      throw new \Exception('Passed networks are invalid.');
    }

    $this->networks = $networks;
  }

  /**
   * Generates the list of tokens required by each message sender class.
   */
  abstract public function generateTokens();

  /**
   * Getter function for results.
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Setter function for message.
   *
   * @param string $message Message to send.
   * @throws \Exception Message needs to be a string.
   */
  public function setMessage($message) {
    if (!is_string($message)) {
      throw new \Exception('Message needs to be a string.');
    }

    // Allow other modules modify the message before it is sent.
    $implementations = \Drupal::moduleHandler()
      ->getImplementations('push_notifications_send_message');
    foreach ($implementations as $module) {
      $function = $module . '_push_notifications_send_message';
      $function($message, $type = 'simple');
    }

    // Truncate the message so that we don't exceed the limit of APNS.
    $this->message = Unicode::truncate($message, PUSH_NOTIFICATIONS_APNS_PAYLOAD_SIZE_LIMIT, TRUE, TRUE);
  }

  /**
   * Dispatch an alert.
   */
  public function dispatch() {
    // Verify that message is set.
    if (empty($this->message)) {
      throw new \Exception('Message was not set correctly.');
    }

    // Generate tokens.
    $this->generateTokens();

    // Log message if no tokens are available.
    if (empty($this->tokens)) {
      \Drupal::logger('push_notifications')->notice('No tokens found.');
      return false;
    }

    // Generate and dispatch message.
    $this->dispatcher->setMessage($this->message);
    $this->dispatcher->setTokens($this->tokens);
    $this->dispatcher->dispatch();
    $this->results = $this->dispatcher->getResults();
  }

}
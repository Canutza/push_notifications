<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsDispatcher.
 */

namespace Drupal\push_notifications;

/**
 * Handles dispatching of messages.
 * This class will send out the message to all networks
 * in the list of tokens.
 */
class PushNotificationsDispatcher {

  /**
   * @var array $tokens
   *   Array of tokens grouped by network.
   */
  protected $tokens = array();

  /**
   * @var string $messages
   *   Message.
   */
  protected $message;

  /**
   * @var array $networks.
   *   Available networks.
   */
  protected $networks;

  /**
   * @var array $results
   *   Results for each network.
   */
  protected $results;

  /**
   * Constructor.
   */
  public function __construct() {
    // Set available networks.
    $this->networks = push_notifications_get_networks();
  }

  /**
   * Dispatch message.
   */
  public function dispatch() {
    foreach ($this->networks as $network) {
      // Only try this network if any tokens are available.
      if (empty($this->tokens[$network])) {
        $this->results[$network] = array(
          'network' => $network,
          'count_attempted' => 0,
          'count_success' => 0,
          'success' => NULL,
        );
        continue;
      }

      // Broadcast message.
      try {
        $service_name = 'push_notifications.broadcaster_' . $network;
        $broadcaster = \Drupal::service($service_name);
        $broadcaster->setTokens($this->tokens[$network]);
        $broadcaster->setMessage($this->message);
        $broadcaster->sendBroadcast();
        $this->results[$network] = $broadcaster->getResults();
      } catch (\Exception $e) {
        //drupal_set_message(t('Your message could not be sent. Please check the log for details.'), 'error');
        \Drupal::logger('push_notifications')->error($e->getMessage());
      }
    }
  }

  /*
   * Setter function for payload.
   *
   * @param mixed $tokens
   */
  public function setTokens($tokens) {
    $this->tokens = $this->groupTokensByNetwork($tokens);
  }

  /**
   * Setter method for message.
   *
   * @param mixed $message
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * Getter function for results.
   */
  public function getResults() {
    return $this->results;
  }

  /**
   * Group tokens by network.
   *
   * @param array $tokens_flat Array of token record objects.
   * @return array $tokens Array of tokens grouped by network.
   */
  private function groupTokensByNetwork($tokens_flat = array()) {
    $tokens = array();
    foreach ($tokens_flat as $token) {
      switch ($token->network) {
        case PUSH_NOTIFICATIONS_NETWORK_ID_IOS:
          $tokens[PUSH_NOTIFICATIONS_NETWORK_ID_IOS][] = $token->token;
          break;

        case PUSH_NOTIFICATIONS_NETWORK_ID_ANDROID:
          $tokens[PUSH_NOTIFICATIONS_NETWORK_ID_ANDROID][] = $token->token;
          break;
      }
    }
    return $tokens;
  }

}

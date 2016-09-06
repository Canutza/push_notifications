<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsBroadcasterInterface.
 */

namespace Drupal\push_notifications;

interface PushNotificationsBroadcasterInterface {

  /**
   * Set tokens.
   *
   * @param array $tokens Token list.
   */
  function setTokens($tokens);

  /**
   * Set payload.
   *
   * @param array $payload Payload.
   * @param null $title
   *
   * @return
   */
  function setMessage($payload, $title);

  /**
   * Send the broadcast.
   */
  function sendBroadcast();

  /**
   * Retrieve results after broadcast was sent.
   *
   * @return array Array of data.
   */
  function getResults();
}
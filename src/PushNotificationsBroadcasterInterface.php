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
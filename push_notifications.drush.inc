<?php

/**
 * @file Contains the code to generate drush commands
 */

/**
 * Implements hook_drush_command().
 */
function push_notifications_drush_command() {
  $commands['clear-push-notifications'] = [
    'description' => 'This command will remove all push notifications and all push notifications tokens. This command is intendend for development.',
    'aliases' => ['clear-pn'],
    'arguments' => [
      'target' => 'Specify if you want to remove everything, only the push notifications, or only the push notifications tokens'
    ],
    'examples' => [
      'drush clear-pn all' => 'Removes everything.',
      'drush clear-pn push-notification' => 'Removes all push notifications.',
      'drush clear-pn push-notifications-token' => 'Removes all push notifications tokens.',
    ],
  ];

  return $commands;
}

/**
 * Drush command logic.
 */
function drush_push_notifications_clear_push_notifications($target = NULL) {

  switch ($target) {
    case 'push-notification':
      $push_notification_ids = \Drupal::entityQuery('push_notification')->execute();
      entity_delete_multiple('push_notification', $push_notification_ids);
      drush_print('All push notifications have been removed.');
      break;
    case 'push-notifications-token':
      $push_notifications_tokens_ids = \Drupal::entityQuery('push_notifications_token')->execute();
      entity_delete_multiple('push_notifications_token', $push_notifications_tokens_ids);
      drush_print('All push notifications tokens have been removed.');
      break;
    case 'all':
      $push_notification_ids = \Drupal::entityQuery('push_notification')->execute();
      $push_notifications_tokens_ids = \Drupal::entityQuery('push_notifications_token')->execute();
      entity_delete_multiple('push_notification', $push_notification_ids);
      entity_delete_multiple('push_notifications_token', $push_notifications_tokens_ids);
      drush_print('All push notifications and push notifications tokens have been removed.');
      break;
    default:
      drush_print('Wrong argument, run "drush clear-pn --help" to see available arguments.');
  }

}

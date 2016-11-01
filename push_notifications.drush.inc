<?php
/**
 * @file Contains the code to generate drush commands
 */

/**
 * Implements hook_drush_command().
 */
function push_notifications_drush_command() {
  $commands['clear-push-notifications'] = [
    'description' => 'This  command will remove all push notifications and all push notifications tokens. This command is intendend for development.',
    'aliases' => ['clear-pn'],
    'examples' => [
      'drush clear-pn' => 'Removes everything.'
    ],
  ];

  return $commands;
}

/**
 * Drush command logic.
 */
function drush_push_notifications_clear_push_notifications() {
  $push_notification_ids = \Drupal::entityQuery('push_notification')->execute();
  $push_notifications_tokens_ids = \Drupal::entityQuery('push_notifications_token')->execute();
  entity_delete_multiple('push_notification', $push_notification_ids);
  entity_delete_multiple('push_notifications_token', $push_notifications_tokens_ids);


  drush_print('All push notifications have been delete');
}
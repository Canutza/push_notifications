<?php

namespace Drupal\push_notifications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PushNotificationsSendMessageForm.
 *
 * @package Drupal\push_notifications\Form
 */
class PushNotificationsSendMessageForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'send_message_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['instructions'] = array(
      '#type' => 'item',
      '#markup' => $this->t('Compose the elements of your push notification message.'),
    );


    $form['message'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Push Message'),
      '#description' => $this->t('Compose the message to send out (@limit characters max.)', array(
        '@limit' => PUSH_NOTIFICATIONS_APNS_PAYLOAD_SIZE_LIMIT,
      )),
      '#required' => TRUE,
      '#size' => 128,
      '#maxlength' => PUSH_NOTIFICATIONS_APNS_PAYLOAD_SIZE_LIMIT,
    );

    // Only show Android option if GCM Api Key is available..
    $recipients_options = array(
      PUSH_NOTIFICATIONS_NETWORK_ID_IOS => t('iOS (Apple Push Notifications)')
    );
    if (!empty(\Drupal::config('push_notifications.gcm')->get('api_key'))) {
      $recipients_options[PUSH_NOTIFICATIONS_NETWORK_ID_ANDROID] = t('Android (Google Cloud Messaging)');
    }
    $form['networks'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Target Networks'),
      '#description' => t('Select the networks you want to reach with this message.'),
      '#options' => $recipients_options,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Send Push Notification'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Make sure at least one network is selected.
    $networks = $form_state->getValue('networks');
    if (empty(array_filter($networks))) {
      $form_state->setErrorByName('networks', $this->t('Please select at least one of the target networks.'));
    }

    // Determine recipients.
    $tokens = push_notifications_get_tokens(array(
      'networks' => $networks,
    ));

    if (empty($tokens)) {
      // Onlyproceed if tokens were found.
      $form_state->setErrorByName('networks', $this->t('No tokens found for your selected networks.'));
    } else {
      // Pass the tokens to the submit handler.
      $form_state->setTemporaryValue('tokens', $tokens);
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messageSender = \Drupal::service('push_notifications.message_sender_list');
    $messageSender->setTokens($form_state->getTemporaryValue('tokens'));
    $messageSender->setMessage($form_state->getValue('message'));
    $messageSender->dispatch();
    $results = $messageSender->getResults();

    // Display result for each network.
    foreach ($results as $network => $result) {
      if (empty($result['count_attempted'])) {
        // Only display results for networks with tokens.
        continue;
      }
      drupal_set_message($this->t('@network: Attempted to send @count_attempted tokens, sent @count_success.', array(
        '@network' => strtoupper($network),
        '@count_attempted' => $result['count_attempted'],
        '@count_success' => $result['count_success'],
      )));
    }
  }

}

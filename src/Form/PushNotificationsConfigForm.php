<?php

namespace Drupal\push_notifications\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PushNotificationsConfigForm.
 *
 * @package Drupal\push_notifications\Form
 */
class PushNotificationsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'push_notifications.fcm',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'push_notifications_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form = parent::buildForm($form, $form_state);

    // Get config.
    $config_fcm = $this->config('push_notifications.fcm');

    // Firebase Cloud Messaging.
    $form['fcm'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Firebase Cloud Messaging'),
      '#description' => $this->t('Enter your Firebase Cloud Messaging details.'),
    );

    $form['fcm']['fcm_api_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Firebase Cloud Messaging API Key'),
      '#description' => t('Enter the API key for your Firebase Cloud project'),
      '#maxlength' => 1024,
      '#default_value' => $config_fcm->get('api_key'),
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Store FCM config.
    $config_fcm = $this->config('push_notifications.fcm');
    $config_fcm->set('api_key', $form_state->getValue('fcm_api_key'));
    $config_fcm->save();
  }

}

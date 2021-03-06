<?php
/**
 * @file
 * Contains Drupal\push_notifications\Form\PushNotificationForm.
 */

namespace Drupal\push_notifications\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\push_notifications\PushNotificationInterface;
use Drupal\push_notifications\PushNotificationsTokenQuery;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Form controller for the push_notification entity edit forms.
 *
 * @ingroup push_notifications
 */
class PushNotificationForm extends ContentEntityForm {
  /**
   * The token query.
   *
   * @var \Drupal\push_notifications\PushNotificationsTokenQuery
   */
  protected $token_query;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('push_notifications.token_query')
    );
  }

  /**
   * PushNotificationForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   * @param \Drupal\push_notifications\PushNotificationsTokenQuery $token_query
   */
  public function __construct(EntityManagerInterface $entity_manager, PushNotificationsTokenQuery $token_query) {
    parent::__construct($entity_manager);
    $this->token_query = $token_query;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\push_notifications\Entity\PushNotification */
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;

    if (!$entity->isSend()) {

      $form['push_target'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Target'),
        '#required' => TRUE,
        '#options' => array(
          'networks' => $this->t('Network'),
          'users' => $this->t('User')
        ),
        '#description' => $this->t('Send a notification by network or to individual users'),
        '#weight' => 3,
      );

      $form['networks'] = array(
        '#type' => 'checkboxes',
        '#multiple' => TRUE,
        '#required' => TRUE,
        '#title' => $this->t('Networks'),
        '#options' => array(
          'apns' => $this->t('Apple'),
          'gcm' => $this->t('Android'),
        ),
        '#description' => $this->t('Select the target networks for this notification.'),
        '#states' => array(
          'visible' => array(
            ':input[name="push_target"]' => array('value' => 'networks'),
          ),
        ),
        '#weight' => 4,
      );

      $form['users'] = array(
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('User'),
        '#target_type' => 'user',
        '#tags' => TRUE,
        '#selection_settings' => [
          // We do not want to send to anonymous users because there may be
          // plenty and it will not be send to just one user
          'include_anonymous' => FALSE,
        ],
        '#states' => array(
          'visible' => array(
            ':input[name="push_target"]' => array('value' => 'users'),
          ),
          'required' => array(
            ':input[name="push_target"]' => array('value' => 'users'),
          ),
        ),
        '#description' => $this->t('Add the users you want to send the notification to separated by a comma.'),
        '#weight' => 5,
      );

    }

    $form['langcode'] = array(
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    );


    $form['#entity_builders']['update_status'] = [$this, 'updateStatus'];

    return $form;
  }

  /**
   * Entity builder updating the push_notification status with the submitted
   * value and also sent the push notification.
   *
   * @param string $entity_type_id
   *   The entity type identifier.
   * @param \Drupal\push_notifications\PushNotificationInterface $push_notification
   *   The push_notification updated with the submitted values.
   * @param array $form
   *   The complete form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\push_notifications\Form\PushNotificationForm::form()
   */
  function updateStatus($entity_type_id, PushNotificationInterface $push_notification, array $form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if (isset($element['#send_status'])) {
      $push_notification->setSend($element['#send_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $push_notification = $this->entity;

    $send = $push_notification->isSend() ? TRUE : FALSE;

    $element['unsend'] = $element['submit'];
    $element['unsend']['#send_status'] = FALSE;
    $element['unsend']['#dropbutton'] = 'save';
    if ($push_notification->isNew()) {
      $element['unsend']['#value'] = $this->t('Save as a draft');
    }
    else {
      if (!$send) {
        $element['unsend']['#value'] = $this->t('Save and keep in draft mode');
      }
      else {
        unset($element['unsend']);
      }
    }
    $element['unsend']['#weight'] = 0;

    $element['send'] = $element['submit'];
    $element['send']['#send_status'] = FALSE;
    $element['send']['#dropbutton'] = 'save';
    if ($push_notification->isNew()) {
      $element['send']['#value'] = $this->t('Save and send push notification');
      $element['send']['#send_status'] = TRUE;
    }
    else {
      if ($send) {
        unset($element['send']);
        drupal_set_message($this->t('This push notification has already been sent.'), 'warning');
      }
      else {
        $element['send']['#value'] = $this->t('Save and send push notification');
        $element['send']['#send_status'] = TRUE;
      }
    }
    $element['send']['#weight'] = 10;

    // Remove the "Save" button.
    $element['submit']['#access'] = FALSE;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.push_notification.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $tokens = array();
    if ($element['#send_status']) {
      $title = $form_state->getValue('title');
      $message = $form_state->getValue('message');
      $push_target = $form_state->getValue('push_target');
      if ($push_target == 'users') {
        $uids = array();
        $target_ids = $form_state->getValue('users');
        foreach ($target_ids as $target_id) {
          array_push($uids, $target_id['target_id']);
        }
        $tokens = $this->token_query->getTokensByUid($uids);
      }
      else {
        if ($push_target == 'networks') {
          $networks = Checkboxes::getCheckedCheckboxes($form_state->getValue('networks'));
          $tokens = $this->token_query->getTokensByNetwork($networks);
        }
      }
      // @TODO: Send notification
      drupal_set_message($this->t('The push notification has been successfully send.'));
    }
    parent::submitForm($form, $form_state);
  }

}
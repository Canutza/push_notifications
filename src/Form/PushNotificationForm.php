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

    if (!$entity->isPushed()) {

      $form['push_target'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Target type'),
        '#required' => TRUE,
        '#options' => array(
          'networks' => $this->t('Network'),
          'users' => $this->t('User')
        ),
        '#description' => $this->t('Send a notification by network or to individual users'),
        '#weight' => 3,
      );

      $form['networks'] = array(
        '#type' => 'select',
        // This is mega annoying, if the type is 'checkboxes' the required state
        // doesn't work. @todo: Research on how to fix it.
        '#title' => $this->t('Networks'),
        '#multiple' => TRUE,
        '#options' => array(
          'ios' => $this->t('Apple'),
          'android' => $this->t('Android'),
        ),
        '#states' => array(
          'visible' => array(
            ':input[name="push_target"]' => array('value' => 'networks'),
          ),
          'required' => array(
            ':input[name="push_target"]' => array('value' => 'networks'),
          ),
        ),
        '#description' => $this->t('Select the target networks for this notification. You can select multiple by hold down the SHIFT key.'),
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
    if (isset($element['#pushed_status'])) {
      $push_notification->setPushed($element['#pushed_status']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    $push_notification = $this->entity;

    $pushed = $push_notification->isPushed() ? TRUE : FALSE;

    $element['unpushed'] = $element['submit'];
    $element['unpushed']['#pushed_status'] = FALSE;
    $element['unpushed']['#dropbutton'] = 'save';
    if ($push_notification->isNew()) {
      $element['unpushed']['#value'] = $this->t('Save as a draft');
    }
    else {
      if (!$pushed) {
        $element['unpushed']['#value'] = $this->t('Save and keep in draft mode');
      }
      else {
        unset($element['unpushed']);
      }
    }
    $element['unpushed']['#weight'] = 0;

    $element['pushed'] = $element['submit'];
    $element['pushed']['#pushed_status'] = FALSE;
    $element['pushed']['#dropbutton'] = 'save';
    if ($push_notification->isNew()) {
      $element['pushed']['#value'] = $this->t('Save and send push notification');
      $element['pushed']['#pushed_status'] = TRUE;
    }
    else {
      if ($pushed) {
        unset($element['pushed']);
        drupal_set_message($this->t('This push notification has already been sent.'), 'warning');
      }
      else {
        $element['pushed']['#value'] = $this->t('Save and send push notification');
        $element['pushed']['#pushed_status'] = TRUE;
      }
    }
    $element['pushed']['#weight'] = 10;

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
   * @param array $input
   * @return array
   */
  public function getCheckedCheckboxes(array $input) {
    // This is copied from the Drupal 8.2 version of the Checkboxes object
    // @todo: Replace this with the actual function on upgrade

    // Browsers do not include unchecked options in a form submission. The
    // FormAPI tries to normalize this to keep checkboxes consistent with other
    // form elements. Checkboxes show up as an array in the form of option_id =>
    // option_id|0, where integer 0 is an unchecked option.
    //
    // @see \Drupal\Core\Render\Element\Checkboxes::valueCallback()
    // @see https://www.w3.org/TR/html401/interact/forms.html#checkbox
    $checked = array_filter($input, function ($value) {
      return $value !== 0;
    });
    return array_keys($checked);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    if ($element['#pushed_status']) {
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
          $networks = $this->getCheckedCheckboxes($form_state->getValue('networks'));
          $tokens = $this->token_query->getTokensByNetwork($networks);
        }
      }

      $messageSender = \Drupal::service('push_notifications.message_sender_list');
      $messageSender->setTokens($tokens);
      $messageSender->setMessage($message[0]['value'], $title[0]['value']);
      $messageSender->dispatch();

      drupal_set_message($this->t('The push notification has been successfully send.'));
    }

    parent::submitForm($form, $form_state); // TODO: Change the autogenerated stub
  }

}
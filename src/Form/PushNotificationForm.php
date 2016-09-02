<?php
/**
 * @file
 * Contains Drupal\push_notifications\Form\PushNotificationForm.
 */

namespace Drupal\push_notifications\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\Query\Sql\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\push_notifications\PushNotificationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the push_notification entity edit forms.
 *
 * @ingroup push_notifications
 */
class PushNotificationForm extends ContentEntityForm  {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\content_entity_example\Entity\Contact */
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
        '#type' => 'checkboxes',
        '#multiple' => TRUE,
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
        ),
        '#description' => $this->t('Add the users you want to send the notification to separated by a comma.'),
        '#weight' => 4,
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
      if ($push_notification->setPushed($element['#pushed_status'])) {
        // @todo: Send notification
        $values = $form_state->getValues();
        drupal_set_message($this->t('The push notification has been successfully send.'));
      }
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
      $element['unpushed']['#value'] = $this->t('Save as unpublished');
    }
    else {
      if (!$pushed) {
        $element['unpushed']['#value'] = $this->t('Save and keep unpublished');
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

}
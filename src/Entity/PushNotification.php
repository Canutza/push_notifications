<?php
/**
 * @file
 * Contains Drupal\push_notifications\Entity\PushNotification.
 */

namespace Drupal\push_notifications\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\push_notifications\PushNotificationInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\user\UserInterface;

/**
 * Defines the push_notification entity.
 *
 * @ContentEntityType(
 *   id = "push_notification",
 *   label = @Translation("Push Notification"),
 *   label_singular = @Translation("Push Notification"),
 *   label_plural = @Translation("Push Notifications"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Push Notification",
 *     plural = "@count Push Notifications"
 *   ),
 *   base_table = "push_notifications",
 *   admin_permission = "administer push notifications",
 *   fieldable = FALSE,
 *   translatable = TRUE,
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "storage_schema" = "Drupal\push_notifications\PushNotificationStorageSchema",
 *     "list_builder" = "Drupal\push_notifications\Entity\Controller\PushNotificationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\push_notifications\Form\PushNotificationForm",
 *       "add" = "Drupal\push_notifications\Form\PushNotificationForm",
 *       "edit" = "Drupal\push_notifications\Form\PushNotificationForm",
 *       "delete" = "Drupal\push_notifications\Form\PushNotificationDeleteForm",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/push_notifications/{push_notification}",
 *     "edit-form" = "/admin/content/push_notifications/{push_notification}/edit",
 *     "delete-form" = "/admin/content/push_notifications/{push_notification}/delete",
 *     "collection" = "/admin/content/push_notifications/list",
 *   },
 * )
 */
class PushNotification extends ContentEntityBase implements PushNotificationInterface {
  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_uid entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isSend() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setSend($send) {
    $this->set('status', $send ? PUSH_NOTIFICATION_SENT : PUSH_NOTIFICATION_DRAFT);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChanged() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenId() {
    return $this->get('tid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime($type = 'short') {
    return \Drupal::service('date.formatter')->format($this->getCreated(), $type);
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime($type = 'short') {
    return \Drupal::service('date.formatter')->format($this->getChanged(), $type);
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Push Notification entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Push Notifications.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored By'))
      ->setDescription(t('The user who created the Push Notification entity'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => 50,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ),
        'weight' => 50,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Push Notification Title'))
      ->setDescription(t('The title of the Push Notification entity.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The message of the Push Notification entity.'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'rows' => 2
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string_long',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_long',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Pushed'))
      ->setDescription(t('The time that the push notification was last edited and send.'));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string_long',
        'weight' => 2,
      ))
      ->setDescription(t('The language code of the entity.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sent status'))
      ->setDescription(t('A boolean indicating whether the push_notification has been sent.'))
      ->setDefaultValue(FALSE);

    return $fields;
  }


}
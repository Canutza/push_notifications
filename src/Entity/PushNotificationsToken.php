<?php

/**
 * @file
 * Contains \Drupal\push_notifications\Entity\PushNotificationsToken.
 */

namespace Drupal\push_notifications\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\push_notifications\PushNotificationsTokenInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the token entity.
 *
 * @ContentEntityType(
 *   id = "push_notifications_token",
 *   label = @Translation("Push Notifications Token"),
 *   base_table = "push_notifications_tokens",
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   handlers = {
 *     "access" = "Drupal\push_notifications\PushNotificationsTokenAccessControlHandler",
 *     "storage_schema" = "Drupal\push_notifications\PushNotificationsTokenStorageSchema",
 *     "list_builder" = "Drupal\push_notifications\Entity\Controller\PushNotificationsTokenListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\push_notifications\Form\PushNotificationsTokenDeleteForm",
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/services/push_notifications/token/list",
 *     "canonical" = "/push_notifications/token/{push_notifications_token}",
 *     "delete-form" = "/push_notifications/token/{push_notifications_token}/delete",
 *   },
 * )
 */
class PushNotificationsToken extends ContentEntityBase implements PushNotificationsTokenInterface {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the uid entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'uid' => \Drupal::currentUser()->id(),
    );

    // Set a default language if no language is passed.
    if (!array_key_exists('langcode', $values)) {
      $values += array(
        'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->get('token')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetwork() {
    return $this->get('network')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageCode() {
    return $this->get('langcode')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTimestamp() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setNetwork($network) {
    $this->set('network', $network);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime($type = 'short') {
    return \Drupal::service('date.formatter')->format($this->getCreatedTimestamp(), $type);
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary key.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('Push notifications ID.'))
      ->setReadOnly(TRUE);

    // UUID field.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the push notifications token.'))
      ->setReadOnly(TRUE);

    // User ID.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The token owner.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // Token.
    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('Device Token'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->addConstraint('PushNotificationsTokenUnique');

    // Network.
    $fields['network'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Network'))
      ->setDescription(t('Network Type'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // Timestamp.
    $fields['created'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp the token was added.'))
      ->setRequired(TRUE)
      ->setDefaultValue(REQUEST_TIME)
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('view', TRUE);

    // Language code.
    // If no language code is provided when entity is created,
    // the language code is set to the default language of the site.
    $fields['langcode'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language'))
      ->setDescription(t('The language associated with this token.'))
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setRequired(TRUE)
      ->addConstraint('PushNotificationsTokenLanguage')
      ->setDisplayOptions('view', array(
        'label' => 'inline',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}

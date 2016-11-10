<?php

namespace Drupal\push_notifications\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * Supports validating the token value of a token entity.
 *
 * @Constraint(
 *   id = "PushNotificationsTokenUserUnique",
 *   label = @Translation("Token value in token entity", context = "Validation"),
 *   type = "entity:push_notifications_token"
 * )
 */
class PushNotificationsTokenUserUniqueConstraint extends CompositeConstraintBase {

  public $message = "A token with the value '@value' already exists for this user. Use a unique token.";

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['token', 'uid'];
  }

}

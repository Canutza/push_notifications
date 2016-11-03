<?php

/**
 * @file
 * Contains Drupal\push_notifications\Plugin\Validation\Constraint\PushNotificationsTokenUserUniqueConstraintValidator.
 */

namespace Drupal\push_notifications\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\push_notifications\PushNotificationsTokenQuery;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator;

class PushNotificationsTokenUserUniqueConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Validator 2.5 and upwards compatible execution context.
   *
   * @var \Symfony\Component\Validator\Context\ExecutionContextInterface
   */
  protected $context;
  /**
   * Token query
   *
   * @var \Drupal\push_notifications\PushNotificationsTokenQuery;
   */
  protected $tokenQuery;

  /**
   * Constructs a new PushNotificationsTokenUserUniqueConstraintValidator
   *
   * PushNotificationsTokenUserUniqueConstraintValidator constructor.
   * @param \Drupal\push_notifications\PushNotificationsTokenQuery $tokenQuery
   */
  public function __construct(PushNotificationsTokenQuery $tokenQuery) {
    $this->tokenQuery = $tokenQuery;
  }

  /**
   * {@inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static ($container->get('push_notifications.token_query'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    $owner_id = $entity->getOwnerId();
    $token = $entity->getToken();
    $token_matches = $this->tokenQuery->checkTokenByUid($token, $owner_id);
    $token_matches_count = count($token_matches);

    if ($token_matches_count == 1) {
      $this->context->buildViolation($constraint->message, array('@value' => $token))
        ->atPath('token')
        ->addViolation();
    }
  }

}
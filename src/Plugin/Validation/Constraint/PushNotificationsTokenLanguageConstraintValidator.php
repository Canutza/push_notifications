<?php

namespace Drupal\push_notifications\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\ChoiceValidator;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Drupal\Core\Language\LanguageManager;

/**
 * Checks if a language code is in the list of accepted language codes.
 */
class PushNotificationsTokenLanguageConstraintValidator extends ChoiceValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    // Determine the language code.
    // Get the data from the typed data list item.
    // @see https://api.drupal.org/api/drupal/core!core.api.php/group/typed_data/8.2.x,
    // Tree handling.
    $typed_data = $this->getTypedData();
    $value = $typed_data->value;

    // Set available choices to all available languages.
    $constraint->choices = array_keys(LanguageManager::getStandardLanguageList());

    parent::validate($value, $constraint);
  }

}


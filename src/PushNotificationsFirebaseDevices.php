<?php

/**
 * @file
 * Contains Drupal\push_notifications\PushNotificationsFirebaseDevices.
 */

namespace Drupal\push_notifications;


class PushNotificationsFirebaseDevices implements PushNotificationsBroadcasterInterface {

  /**
   * Firebase Cloud Messaging Endpoint
   */
  const PUSH_NOTIFICATIONS_FCM_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

  /**
   * @var array $tokens
   *   List of tokens.
   */
  protected $tokens;

  /**
   * @var array $payload
   *   Payload.
   */
  protected $data_payload;

  /**
   * @var array $notification
   *   Notification.
   */
  protected $notification;

  /**
   * @var string $priority
   *   The priority of the notification.
   */
  protected $priority;

  /**
   * @var int $countAttempted
   *   Count of attempted tokens.
   */
  protected $countAttempted = 0;

  /**
   * @var int $countSuccess
   *   Count of successful tokens.
   */
  protected $countSuccess = 0;

  /**
   * @var bool $success
   *   Flag to indicate success of all batches.
   */
  protected $success = FALSE;

  /**
   * @var int $tokenBundles
   *   Number of token bundles.
   */
  private $tokenBundles;

  /**
   * Set tokens.
   *
   * @param array $tokens Token list.
   */
  function setTokens($tokens) {
    $this->tokens = $tokens;
  }

  /**
   * Set payload.
   *
   * @param array $data_payload Payload.
   */
  function setDataPayload($data_payload) {
    $this->data_payload = $data_payload;

  }

  /**
   * Set notification data.
   *
   * @param $body
   * @param $title
   */
  function setNotification($body, $title) {
    $this->notification = array(
      'title' => $title,
      'body' => $body
    );

  }

  /**
   * Set the priority of the notifications
   *
   * @param string $priority Priority
   */
  function setPriority($priority) {
    $this->priority = $priority;
  }


  /**
   * Send the broadcast message.
   *
   * @return bool
   */
  public function sendBroadcast() {
    if (empty($this->tokens)) {
      $message = t('No tokens have been found.');
      \Drupal::logger('push_notifications')->alert($message);
      drupal_set_message($message, 'error');

      return FALSE;
    }

    // Set token bundles.
    $this->tokenBundles = ceil(count($this->tokens) / 1000);

    // Set number of tokens to attempt.
    $this->countAttempted = count($this->tokens);

    // Send notifications in slices of 1000
    // and process the results.
    for ($i = 0; $i < $this->tokenBundles; $i++) {
      try {
        $bundledTokens = array_slice($this->tokens, $i * 1000, 1000, FALSE);
        $result = $this->sendTokenBundle($bundledTokens);
        $this->processResult($result, $bundledTokens);
      } catch (\Exception $e) {
        \Drupal::logger('push_notifications')->error($e->getMessage());
        drupal_set_message($e->getMessage(), 'error');

        return FALSE;
      }
    }

    // Mark success as true.
    return $this->success = TRUE;
  }

  /**
   * Send a token bundle.
   *
   * @param array $tokens
   *   Array of tokens.
   * @returns array
   *   Returns return of curl info and response from GCM.
   */
  private function sendTokenBundle($tokens) {
    // Convert the payload into the correct format for payloads.
    // Prefill an array with values from other modules first.
    $data = array();

    // Fill the default values required for each payload.
    $data['registration_ids'] = $tokens;
    $data['collapse_key'] = (string) time();
    if (isset($this->payload)) {
      $data['data'] = $this->payload;
    }
    $data['notification'] = $this->notification;
    $data['priority'] = $this->priority;
    if (!empty($this->data_payload)) {
      $data['data'] = $this->data_payload;
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::PUSH_NOTIFICATIONS_FCM_ENDPOINT);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $this->getHeaders());
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_POST, TRUE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    $response_raw = curl_exec($curl);
    $info = curl_getinfo($curl);
    curl_close($curl);

    $response = FALSE;
    if (isset($response_raw)) {
      $response = json_decode($response_raw);
    }

    return array(
      'info' => $info,
      'response' => $response,
    );
  }

  /**
   * Process the a batch result.
   *
   * @param array $result
   *   Result of a bundle process, containing the curl info, response, and raw response.
   * @param array $tokens
   *   Tokens bundle that was processed.
   * @throws \Exception
   *   Throw Exception when connection with Google play cannot be authenticated.
   */
  private function processResult($result, $tokens) {
    // If connection is unauthorized, throw Exception.
    if ($result['info']['http_code'] != 200) {
      throw new \Exception('Connection could not be authorized with FCM. Please check if your key is valid.');
    }

    // If Google returns a 200 reply, but that reply includes an error,
    // log the error message.
    if ($result['info']['http_code'] == 200 && ($result['response']->failure > 0)) {
      // Analyze the failure.
      foreach ($result['response']->results as $token_index => $message_result) {
        if (!empty($message_result->error)) {
          // If the device token is invalid or not registered (anymore because the user
          // has uninstalled the application), remove this device token.
          if ($message_result->error == 'NotRegistered' || $message_result->error == 'InvalidRegistration') {
            $entity_type = 'push_notifications_token';
            $query = \Drupal::entityQuery($entity_type)->condition('token', $tokens[$token_index]);
            $entity_ids = $query->execute();
            $entityTypeManager = \Drupal::entityTypeManager()->getStorage($entity_type);
            $entity = $entityTypeManager->load(array_shift($entity_ids));
            $entity->delete();
            \Drupal::logger('push_notifications')->notice("FCM token not valid anymore. Removing token '@token', for uid '@uid' and network '@network'.", array(
              '@token' => $tokens[$token_index],
              '@uid' => $entity->getOwnerId(),
              '@network' => $entity->getNetwork(),
            ));
          }
        }
      }
    }

    // Count the successful sent push notifications if there are any.
    if ($result['info']['http_code'] == 200 && !empty($result['response']->success)) {
      $this->countSuccess += $result['response']->success;
    }
  }

  /**
   * Retrieve results after broadcast was sent.
   *
   * @return object
   */
  function getResults() {
    return (object) array(
      'data_payload' => $this->data_payload,
      'notification' => $this->notification,
      'count_attempted' => $this->countAttempted,
      'count_success' => $this->countSuccess,
      'success' => $this->success,
    );
  }

  /**
   * Get the headers for sending broadcast.
   */
  private function getHeaders() {
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key=' . \Drupal::config('push_notifications.fcm')->get('api_key');
    return $headers;
  }
}
<?php

/**
 * @file
 * Contains \Drupal\push_notifications\PushNotificationsBroadcasterGcm.
 */

namespace Drupal\push_notifications;

/**
 * Broadcasts Android messages.
 */
class PushNotificationsBroadcasterGcm implements PushNotificationsBroadcasterInterface {

  /**
   * GCM notification post URL.
   */
  const PUSH_NOTIFICATIONS_GCM_SERVER_POST_URL = 'https://android.googleapis.com/gcm/send';

  /**
   * @var array $tokens
   *   List of tokens.
   */
  protected $tokens;

  /**
   * @var array $payload
   *   Payload.
   */
  protected $payload;

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
   * @var string $statusMessage
   *   Status messages.
   */
  protected $message;

  /**
   * @var int $tokenBundles
   *   Number of token bundles.
   */
  private $tokenBundles;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Setter function for tokens.
   *
   * @param $tokens
   */
  public function setTokens($tokens) {
    $this->tokens = $tokens;
  }

  /**
   * Setter function for message.
   *
   * @param $message
   */
  public function setMessage($message) {
    $this->message = $message;

    // Set the payload.
    $this->payload = array(
      'alert' => $message,
    );
  }

  /**
   * Send the broadcast message.
   *
   * @throws \Exception
   *   Array of tokens and payload necessary to send out a broadcast.
   */
  public function sendBroadcast() {
    if (empty($this->tokens) || empty($this->payload)) {
      throw new \Exception('No tokens or payload set.');
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
      }
    }

    // Mark success as true.
    $this->success = TRUE;
  }

  /**
   * Get the results of a batch.
   */
  public function getResults() {
    return array(
      'network' => PUSH_NOTIFICATIONS_NETWORK_ID_ANDROID,
      'payload' => $this->payload,
      'count_attempted' => $this->countAttempted,
      'count_success' => $this->countSuccess,
      'success' => $this->success,
    );
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
    foreach ($this->payload as $key => $value) {
      if ($key != 'alert') {
        $data['data'][$key] = $value;
      }
    }
    // Fill the default values required for each payload.
    $data['registration_ids'] = $tokens;
    $data['collapse_key'] = (string) time();
    $data['data']['message'] = $this->message;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, self::PUSH_NOTIFICATIONS_GCM_SERVER_POST_URL);
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
      'response_raw' => $response_raw,
    );
  }

  /**
   * Process the a batch result.
   *
   * @param array $result
   *   Result of a bundle process, containing the curl info, reponse, and raw response.
   * @param array $tokens
   *   Tokens bundle that was processed.
   * @throws \Exception
   *   Throw Exception when connection with Google play cannot be authenticated.
   */
  private function processResult($result, $tokens) {
    // If connection is unauthorized, throw Exception.
    if ($result['info']['http_code'] != 200) {
      throw new \Exception('Connection could not be authorized with Google Play. Check your API key.');
    }

    // If Google returns a 200 reply, but that reply includes an error,
    // log the error message.
    if ($result['info']['http_code'] == 200 && (!empty($result['response']->failure))) {
      \Drupal::logger('push_notifications')->notice("Google's Server returned an error: @response_raw", array(
        '@response_raw' => $result['response_raw'],
      ));

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
            \Drupal::logger('push_notifications')->notice("GCM token not valid anymore. Removing token @token", array(
              '@$token' => $tokens[$token_index],
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
   * Get the headers for sending broadcast.
   */
  private function getHeaders() {
    $headers = array();
    $headers[] = 'Content-Type:application/json';
    $headers[] = 'Authorization:key=' . \Drupal::config('push_notifications.gcm')->get('api_key');
    return $headers;
  }
}
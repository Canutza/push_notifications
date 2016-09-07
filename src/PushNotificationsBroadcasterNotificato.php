<?php
/**
 * @file
 * Contains Drupal\push_notifications\PushNotificationsBroadcasterNotificato.
 */

namespace Drupal\push_notifications;

use Wrep\Notificato\Notificato;

class PushNotificationsBroadcasterNotificato implements PushNotificationsBroadcasterInterface {

  /**
   * @var Notificato
   */
  protected $notificato;

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
   *   Flag to indicate success.
   */
  protected $success = FALSE;

  /**
   * @var string $statusMessage
   *   Status messages.
   */
  protected $message;

  /**
   * @var stream $apns
   *   APNS connection.
   */
  protected $apns;

  /**
   * @var string $certificate_path
   *   Absolute certificate path.
   */
  private $certificate_path;

  /**
   * @var object $config
   *   APNS configuration object.
   */
  private $config;

  /**
   * @var string $gateway
   *   APNS gateway.
   */
  private $gateway;

  /**
   * PushNotificationsBroadcasterNotificato constructor.
   */
  public function __construct() {
    // Load configuration.
    $this->config = \Drupal::config('push_notifications.apns');


    // Determine certificate path.
    $this->determineCertificatePath();

    // Determine correct gateway.
    $this->determineGateway();

    $this->notificato = new Notificato($this->certificate_path, 'ionut');
  }

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
   * @param array $message
   * @param null $title
   *
   * @internal param array $payload Payload.
   */
  function setMessage($message, $title) {
    $this->message = $message;

    // Set the payload.
    $this->payload = array(
      'alert' => $message,
    );
  }

  /**
   * Determine the correct gateway.
   */
  private function determineGateway() {
    switch ($this->config->get('environment')) {
      case 'development':
        $this->gateway = 'gateway.sandbox.push.apple.com';
        break;
      case 'production':
        $this->gateway = 'gateway.push.apple.com';
        break;
    }
  }

  /**
   * Determine the realpath to the APNS certificate.
   *
   * @see http://stackoverflow.com/questions/809682
   * @throws \Exception
   *   Certificate file needs to be set
   */
  private function determineCertificatePath() {
    // Determine if custom path is set.
    $path = $this->config->get('certificate_folder');

    // If no custom path is set, get module directory.
    if (empty($path)) {
      $path = drupal_realpath(drupal_get_path('module', 'push_notifications'));
      $path .= DIRECTORY_SEPARATOR . 'certificates' . DIRECTORY_SEPARATOR;
    }

    // Append name of certificate.
    $path .= push_notifications_get_certificate_name($this->config->get('environment'));

    if (!file_exists($path)) {
      throw new \Exception(t("Cannot find apns certificate file at @path", array(
        '@path' => $path,
      )));
    }

    $this->certificate_path = $path;
  }

  /**
   * Send the broadcast.
   */
  function sendBroadcast() {
    if (empty($this->tokens) || empty($this->payload)) {
      throw new \Exception('No tokens or payload set.');
    }

    $messageEnvelopes = array();
    $builder = $this->notificato->messageBuilder()
      ->setExpiresAt(new \DateTime('+1 hour'))
      ->setSound()
      ->setBadge(1)
      ->setAlert($this->message, 'to enter app')
      ->setContentAvailable(true);


    foreach ($this->tokens as $token) {
      // Update the message for this device
      $builder->setDeviceToken($token);

      // Queue the message for sending
      $messageEnvelopes[] = $this->notificato->queue($builder->build());

    }

    // Now all messages are queued, lets send them at once
    // Be aware that this method is blocking and on failure Notificato will retry if necessary
    $this->notificato->flush();
    // The returned envelopes contains usefull information about how many retries where needed and if sending succeeded
    foreach ($messageEnvelopes as $messageEnvelope) {
      \Drupal::logger('push_notifications')->notice("APNS message: Identifier: @identifier, Status description @status_description", array(
        '@identifier' => $messageEnvelope->getIdentifier(),
        '@status_description' => $messageEnvelope->getFinalStatusDescription(),
      ));
    }

    // Mark success as true.
    $this->success = TRUE;
  }

  /**
   * Retrieve results after broadcast was sent.
   *
   * @return array Array of data.
   */
  function getResults() {
    return array(
      'network' => PUSH_NOTIFICATIONS_TYPE_ID_IOS,
      'payload' => $this->payload,
      'count_attempted' => $this->countAttempted,
      'count_success' => $this->countSuccess,
      'success' => $this->success,
    );
  }
}
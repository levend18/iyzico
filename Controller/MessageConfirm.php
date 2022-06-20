<?php

namespace Drupal\iyzico\Controller;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

// Use the REST API Client to make requests to the Twilio REST API
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * trainings controller.
 */
class MessageConfirm extends ControllerBase {

  private \Drupal\Core\Entity\EntityStorageInterface $paymentStorage;

  private string $addressId;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->paymentStorage = \Drupal::entityTypeManager()
      ->getStorage('payments');

    $this->addressId = "user_address_id";
  }

  public function SendMessage() {
    $webForm_submission = $this->getSessionsForAddressId();
    if
    (
      \Drupal::request()->query->get('smsverify')
      ==
      $webForm_submission->getData()['message_key']
    ) {
      $webForm_submission->setElementData('is_user_confirmed_via_message', TRUE);
      $webForm_submission->save();

      $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
      header("Location: $actual_link" . "/payments");
      die();

    }

    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    header("Location: $actual_link" . "/odeme-sms-dogrulama?response=wrongMesage");
    die();

  }


  private function getSessionsForAddressId(): array {

    $tempstore = \Drupal::service('tempstore.private')->get('address');

    $nIds = $this->paymentStorage->getQuery()
      ->condition('field_session_id', $tempstore->get($this->addressId))
      ->sort('id', 'desc')
      ->execute();
    return $this->paymentStorage->loadMultiple($nIds);

  }

}

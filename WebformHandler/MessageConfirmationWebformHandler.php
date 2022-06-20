<?php

namespace Drupal\iyzico\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "example2_form_handler",
 *   label = @Translation("Example2 form handler"),
 *   category = @Translation("Examples2"),
 *   description = @Translation("An example2 form handler"),
 *   cardinality =
 *   Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE, results
 *   = Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class MessageConfirmationWebformHandler extends WebformHandlerBase {

  private $addressId;

  private $sid;

  private $token;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->addressId = "user_address_id";

    $this->sid = "AC26406e4738d8ce256703a0a20b711a17";

    $this->token = "e317c37ab85a84828b77d0a713a76097";

  }

  /**
   * {@inheritdoc}
   */

  public function preSave(WebformSubmissionInterface $webform_submission) {

    $message_key = rand(111111, 999999);
    $message_key  = "123456";
    $values = $webform_submission->getData();
    $values['message_key'] = $message_key;
    $webform_submission->setData($values);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {

    $values = $webform_submission->getData();

 //   $this->setSessionsForAddressId($webform_submission->id());
    $client = new Client($this->sid, $this->token);
    try {
      $clients = $client->messages->create(
        '+90' . $values['cep_telefonunuz'],
        [
          'from' => '+15153688258',
          'body' => 'Pazarlama Akademisi doğrulama kodu ile
          işleminize devam edebilirsiniz :' . $values['message_key'],
        ]
      );

    } catch (TwilioException $e) {
      \Drupal::logger('iyzico')->notice($e);
    }
  }

}

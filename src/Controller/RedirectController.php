<?php

namespace Drupal\iyzico\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\iyzico\Services\Baskets\BasketService;
use Drupal\paymentfirst\Controller\RequestedFormsController;
use Drupal\trainings\Controller\BasketsController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This is a controller for 3D secure payment.
 */
class RedirectController extends ControllerBase {

  private BasketService $basket;

  protected $configFactory;

  private Request $requestsAll;

  private string $addressId;

  private $payments;

  private $basketResultId;

  private \Drupal\Core\Entity\EntityStorageInterface $paymentStorage;

  public function __construct(BasketService $basket) {
    $this->basket = $basket;
    $this->requestsAll = \Drupal::request();
    $this->addressId = "user_address_id";
    $this->payments = \Drupal::entityTypeManager()
      ->getStorage('success_payments');
    $this->paymentStorage = \Drupal::entityTypeManager()
      ->getStorage('payments');
  }

  public static function create(ContainerInterface $container): RedirectController {
    $paymentService = $container->get('izyico.baskets');

    return new static($paymentService);
  }

  public function getStatus() {

}
  public function callback() {
    setcookie('same-site-cookie', 'foo', ['samesite' => 'Lax']);
    setcookie('cross-site-cookie', 'bar', [
      'samesite' => 'None',
      'secure' => TRUE,
    ]);
    if (!isset($_POST['token'])) {
      $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
      header("Location: $actual_link" . "/payments?error=odeme başarısız");
      die();
    }
    $tempstore = \Drupal::service('tempstore.private')->get('address');

    $addressId = $tempstore->get($this->addressId);
    $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
    $request->setLocale(\Iyzipay\Model\Locale::TR);
    $request->setConversationId($addressId);
    $request->setToken($_POST['token']);
    $tempstoreTrainings = \Drupal::service('tempstore.private')
      ->get('trainings');

    $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $this->basket->getOptions());

    if ($checkoutForm->getPaymentStatus() == "FAILURE") {
      $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
      header("Location: $actual_link" . "/payments?error=odeme başarısız");
      die();
    }
    else {
      if (is_null($checkoutForm->getToken())) {
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
        header("Location: $actual_link" . "/payments?error=odeme başarısız");
        die();
      }
      $this->setSessionsForAddressId(substr($checkoutForm->getBasketId(), 1));
      $this->basketResultId = substr($checkoutForm->getBasketId(), 1);
      $this->buyBasketItems();
      $this->sendAcceptedMail();
    }
    $tempstoreTrainings->set($this->basket->getBasketsId(), []);
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
    header("Location: $actual_link" . "/odeme-ok");
    die();
  }

  private function buyBasketItems() {

    $saveArray = [];
    $saveArray['type'] = "success_payments";
    $saveArray['field_session_id'] = $this->basketResultId;
    $saveArray['field_is_crun_run'] = "off";
    $saveArray['field_odeme_referance'] = $this->getSessionsForAddressId();
    $this->payments->create($saveArray)->save();
  }

  private function sendAcceptedMail() {


    $items = $this->basket->getSessionsForAddressId();
    $values = [];

    foreach ($items as $item) {
      $values['e_mail_adresiniz'] = $item->field_e_mail_adresiniz[0]->value;
    }


    $ch = curl_init();

    $json = [
      "ToAddresses" => [
        $values['e_mail_adresiniz'],
      ],
      "Subject" => " Pazarlama Akademisi | Satın Alınan Eğitim/Atölye Detayları",
      "MailBody" => $this->basket->getMailBodyV2(),
      "Sender" => "noreply@pazarlamaakademisi.com",
    ];


    curl_setopt($ch, CURLOPT_URL, "https://k4gkjspta9.execute-api.eu-north-1.amazonaws.com/Test/sendemailtransactional");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'x-api-key: A8UBXc2Rrb67bn8Y6zJ6A6odc8LnUwkO8spqIkw5',
      'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_exec($ch);

    curl_close($ch);
    /*
        // Further processing ...
        if ($server_output == "OK") {
        }
        else {
          dd($server_output);
        }
    */
  }

  private function setSessionsForAddressId($id) {
    $tempstore = \Drupal::service('tempstore.private')->get('address');
    $tempstore->set('user_address_id', $id);
  }

  private function getSessionsForAddressId() {


    $nIds = $this->paymentStorage->getQuery()
      ->condition('field_session_id', $this->basketResultId)
      ->sort('id', 'desc')
      ->execute();
    return array_key_first($nIds);

  }

}

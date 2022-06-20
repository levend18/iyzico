<?php

namespace Drupal\iyzico\Services\Baskets;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Currency;
use Iyzipay\Model\Locale;
use Iyzipay\Model\PaymentGroup;
use Iyzipay\Options;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 */
abstract class BasketServiceAbstract {

  protected string $basketsId;

  protected CreateCheckoutFormInitializeRequest $formInitialRequest;

  protected Options $options;

  protected ConfigFactoryInterface $configFactory;

  private \Drupal\Core\Entity\EntityStorageInterface $paymentStorage;

  private string $addressId;

  protected string $content;

  protected $basketsSessionNodes;

  protected array $basketsSessionsIds;

  protected $basketsSessions;


  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->addressId = "user_address_id";
    $this->basketsId = '_baskets_10';

    $this->formInitialRequest = new CreateCheckoutFormInitializeRequest();

    $this->options = new Options();

    $this->configFactory = \Drupal::configFactory();

    $this->options->setApiKey($this->configFactory->get('iyzico.iyzicoadminkeys')
      ->get('api_key'));
    $this->options->setSecretKey($this->configFactory->get('iyzico.iyzicoadminkeys')
      ->get('secret_key'));
    $this->options->setBaseUrl($this->configFactory->get('iyzico.iyzicoadminkeys')
      ->get('api_url'));

    $this->paymentStorage = \Drupal::entityTypeManager()
      ->getStorage('payments');


  }

  protected function initializeBasketSettings() {

    $tempstore = \Drupal::service('tempstore.private')->get('trainings');

    $this->basketsSessions = json_decode($_COOKIE[$this->basketsId]);

    $this->basketsSessionsIds = $this->convertArraytoIds($this->basketsSessions);

    $this->basketsSessionNodes = Node::loadMultiple($this->basketsSessionsIds);
  }

  protected function initializeBasketSettingsWithoutUnique() {

    $tempstore = \Drupal::service('tempstore.private')->get('trainings');

    $this->basketsSessions =  json_decode($_COOKIE[$this->basketsId]);

    $this->basketsSessionsIds = $this->convertArrayToIdsWithoutUniques($this->basketsSessions);

    $this->basketsSessionNodes = Node::loadMultiple($this->basketsSessionsIds);
  }

  protected function getBaskets(): string {

    $this->initializeBasketSettings();
    $nodes = $this->basketsSessionNodes;
    $sumCount = 0;


    $count = array_count_values($this->basketsSessions);


    foreach ($count as $basketsSession) {
      $sumCount += $basketsSession;
    }

    $response = '<div class="cart">
    <form action="">
      <div class="cartHeader">
        <h5>Sepetin <i class="cartItemCount">' . 11 . '</i></h5>
        <a href="" class="cartClose closeCart">
          <img src="https://dvsd0f8pn5yuv.cloudfront.net/images/close.svg" alt="sepeti kapat">
        </a>
      </div>
      <div class="cartBody">';
    $itemCount = 0;
    $sumPrice = 0;
    foreach ($nodes as $item) {
      if ($sumCount == 0) {
        continue;
      }
      $image = $item->field_field_image->entity;
      if (is_null($image)) {
        continue;
      }
      $imageUrl = str_replace('public://', '/sites/default/files/', $image->getFileUri());
      $title = $item->getTitle();
      $id = $item->nid->value;
      $body = $item->body->value;
      $field_item_old_number = $item->field_item_old_number->value;
      $field_item_current_number = $item->field_item_current_number->value;
      $field_teacher = $item->field_item_user_name->value;


      $SameItemCounts = [];
      if ($item->field_item_stock_and_date != NULL) {

        foreach ($item->field_item_stock_and_date as $it) {
          if (in_array($item->id() . " + " . $it->value, $this->basketsSessions)) {
            $SameItemCounts[] = $item->id() . " + " . $it->value;

          }
        }

        foreach ($SameItemCounts as $key => $SameItemCount) {

          $urlencodeSameItemCount = str_replace([
            " ",
            "+",
            ".",
            ';',
            ':',
            '-',
          ], ["", "", "", "", "", ""], $SameItemCount);
          $selectStock = '';
          foreach ($item->field_item_stock_and_date as $it) {
            $dates = explode('; ', $it->value);

            if ($SameItemCount == $item->id() . " + " . $it->value) {
              $selectStock .= '<option selected value="' . urlencode($it->value) . '">' . $this->dateToRequestedDate($dates[1]) . '</option>';
            }

          }


          $stockMax = explode(' + ', $SameItemCount);

          $stockMax[0] = explode(';', $stockMax[1]);
          $stockMax = $stockMax[0];


          $response .= '<div class="cartItem" data-item-price="' . $field_item_current_number . '" data-item-exprice="' . $field_item_old_number . '">
          <div class="row">
            <div class="col-md-4">
              <img src="' . $imageUrl . '" class="img-fluid"
                   alt="">

            </div>
            <div class="col-md-8">
              <strong class="headline">' . $title . '</strong>
              <span class="title">' . $field_teacher . '</span>
              <small class="info">' . $body . ' </small>
              <div class="cartItemInfo">
                <span class="prices d-block"><em class="newPrice">' . $field_item_current_number . '</em> </span>
              <div class="clickCounter  counter_' . $urlencodeSameItemCount . '" data-max-buy-piece="' . $stockMax[0] . '">
                 <span class="minus" onclick="downCartItems(' . "'$SameItemCount'" . ')">-</span>
                  <small id="itemCounter_' . $urlencodeSameItemCount . '">' . $count[$SameItemCount] . '</small>
                  <input type="hidden">
                  <span class="plus" onclick="addCartItems(' . "'$SameItemCount'" . ')">+</span>
                </div>

                <a href="#" id="eraseItem" name="' . $item->id() . '" class="eraseCartItem" onclick="eraseCartItems(' . "'$SameItemCount'" . ')">
                                <img src="/sites/default/files/img/trash-icon.svg" alt=""></a>
              </div>
              <span class="courseDate w-100 db" style="text-align:right;margin-top: 15px;padding-left: 30px;position: relative;">
              <img src="https://dvsd0f8pn5yuv.cloudfront.net/images/date-icon.svg" style="position: absolute;margin-top: -1px;left: 0;" alt="">
              <small>
              <select name="stockAndDate" id="stockAndDate_' . $urlencodeSameItemCount . '" onchange="dateChangesCartItem(' . "'$urlencodeSameItemCount'" . ',' . "'$id'" . ')">
           ' . $selectStock . '
              </select>
              </small>
          </span>
            </div>
          </div>
        </div>
      ';

          $itemCount += $count[$SameItemCount];

          $sumPrice += $field_item_current_number * $count[$SameItemCount];
        }
      }

    }
    if ($nodes == []) {
      $response .= '</div>
      <div class="cartFooter">
        <p>
          <span>Toplam</span>
          <small id="total">' . $sumPrice . '</small>
          <small id="itemCount" style="display: none">' . $itemCount . '</small>
        </p>


        <a  href="/" style="opacity:.5" onclick="return false;" class="btn blue">ALIŞVERİŞİ TAMAMLA</a>
        <a  href="/"  class="closeCart bottomText" >Alışverişe Devam Et</a>
      </div>
    </form>
  </div>
';
    }
    else {
      $response .= '</div>
      <div class="cartFooter">
        <p>
          <span>Toplam</span>
          <small id="total">' . $sumPrice . '</small>
          <small id="itemCount" style="display: none">' . $itemCount . '</small>
        </p>


       ' . $this->getConfirmUrl() . '
        <a  href="/" class="closeCart bottomText" >Alışverişe Devam Et</a>
      </div>
    </form>
  </div>
';
    }


    return $response;
  }

  private function getConfirmUrl(): string {
    if (isset($_SERVER['HTTP_REFERER'])) {
      $url = strtok($_SERVER['HTTP_REFERER'], '?');
      $url = explode('/', $url);
      if (isset($url[3]) && ($url[3] == 'payments_v2' || $url[3] == 'payments')) {
        return '';
      }
    }
    return '<a  href="/payments_v2" class="btn blue">ALIŞVERİŞİ TAMAMLA</a>';

  }

  protected function getMailBody(): string {
    $items = $this->getSessionsForAddressId();
    $values = [];
    foreach ($items as $item) {
      $values['adiniz_ve_soyadiniz'] = $item->field_adiniz_ve_soyadiniz[0]->value;

    }

    $this->initializeBasketSettings();
    $nodes = $this->basketsSessionNodes;
    $sumCount = 0;

    $count = array_count_values($this->basketsSessions);


    foreach ($count as $basketsSession) {
      $sumCount += $basketsSession;
    }

    $response = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pazarlama Akademisi</title>
</head>
<body style="margin:0; padding:0">
<table width="573" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:34px solid #F9FAFA;border-left:34px solid #F9FAFA;border-right:34px solid #F9FAFA">
  <tr>
    <td align="left" valign="top"><table width="573" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="left" valin="top"><img src="https://dvsd0f8pn5yuv.cloudfront.net/images/PA_Logo.svg" alt="Pazarlama Akademisi" width="290" height="93" border="0" style="display:block; border:none"/></td>
          <td align="right" valin="bottom"><table width="165" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td align="left" valign="top" width="40"><img src="images/cart.png" alt="Pazarlama Akademisi" width="25" height="25" border="0" style="display:block; border:none"/></td>
                <td align="left" valign="middle" width="125" style="font-family:Arial,sans-serif;font-size:14px;line-height:14px;font-weight:bold;color:#0e1a39">EĞİTİMLER</td>
              </tr>
            </table></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td align="center" valign="top"><table width="500" border="0" cellpadding="0" cellspacing="0">
        <tr>


          <td align="left" valign="top" style="border-top:2px solid #e3e4e9">&nbsp;</td>
        </tr>
        <tr>
          <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:20px;font-weight:normal;color:#0e1a39">Merhaba <strong>Hale Köksal</strong>,<br />
            <br />
            <strong>Satın almış olduğunuz atölye/kurs bilgileri aşağıdaki gibidir.</td>
        </tr>
        <tr>
          <td height="30">&nbsp;</td>
        </tr>
        <tr>
          <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:24px;line-height:24px;font-weight:bold;color:#0e1a39">Detaylar</td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top"><table width="500" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" valign="top" bgcolor="#f9fafa" style="background-color:#f9fafa"><table width="450" border="0" cellpadding="0" cellspacing="0">

';
    $itemCount = 0;
    $sumPrice = 0;
    $selectStock = '';
    foreach ($nodes as $item) {
      if ($sumCount == 0) {
        continue;
      }
      $image = $item->field_field_image->entity;
      if (is_null($image)) {
        continue;
      }
      $imageUrl = str_replace('public://', '/sites/default/files/', $image->getFileUri());
      $title = $item->getTitle();
      $id = $item->nid->value;
      $body = $item->body->value;
      $field_item_old_number = $item->field_item_old_number->value;
      $field_item_current_number = $item->field_item_current_number->value;
      $field_teacher = $item->field_item_user_name->value;
      $field_zoom_link = $item->field_zoom_link->value;

      $SameItemCounts = [];
      if ($item->field_item_stock_and_date != NULL) {

        foreach ($item->field_item_stock_and_date as $it) {
          if (in_array($item->id() . " + " . $it->value, $this->basketsSessions)) {
            $SameItemCounts[] = $item->id() . " + " . $it->value;

          }
        }

        foreach ($item->field_item_stock_and_date as $it) {
          $dates = explode('; ', $it->value);
          $selectStock .= $this->dateToRequestedDate($dates[1]) . " ";
        }

        foreach ($SameItemCounts as $SameItemCount) {


          $selectStock = '';


          $stockMax = explode(' + ', $SameItemCount);

          $stockMax[0] = explode(';', $stockMax[1]);
          $stockMax = $stockMax[0];


          $response .= '
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:22px;line-height:35px;font-weight:bold;color:#0e1a39;text-decoration:underline">' . $title . '</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:18px;line-height:30px;font-weight:bold;color:#0e1a39">' . $field_teacher . '</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:20px;font-weight:normal;color:#0e1a39">' . $body . '</td>
              </tr>
              <tr>
                <td style="border-bottom:2px solid #e5e7e9">&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:24px;font-weight:normal;color:#0e1a39"><strong>' . $selectStock . '</strong></td>
              </tr>
              <tr>
                <td height="10"></td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:24px;font-weight:normal;color:#0e1a39">Link: <a href="' . $field_zoom_link . '" target="_blank" style="color:#0943db">' . $field_zoom_link . '</a></td>
              </tr>
              <tr>
                <td style="border-bottom:2px solid #e5e7e9">&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>

';

          $itemCount += $count[$SameItemCount];

          $sumPrice += $field_item_current_number * $count[$SameItemCount];
        }
      }

    }


    $response .= '    </table></td>
 </tr>
      </table></td>
  </tr><tr>
    <td height="34">&nbsp;</td>
  </tr>
  <tr>
    <td align="left" valign="top" bgcolor="#f9fafa" style="background-color:#f9fafa"><table width="573" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA" style="font-family:Arial,sans-serif;font-size:16px;line-height:22px;font-weight:normal;color:#0e1a39">Sorularınız varsa bu e-postayı yanıtlayın veya<br />
            <a href="mailto:destek@pazarlamaakademisi.com" style="color:#0e1a39;text-decoration:underline;font-weight:bold">destek@pazarlamaakademisi.com</a> adresinden bizimle<br />
            iletişime geçin.<br />
            <br />
            Pazarlama Akademisi Danışmanlık Hizmetleri A.Ş<br />
            Levent, Esentepe Mah. Büyükdere Cad. Ecza Sokak Safter Han<br />
            İş Merkezi, D:No:6 Kat:1 14394</td>
        </tr>
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA" style="font-family:Arial,sans-serif;font-size:16px;line-height:22px;font-weight:bold;color:#0e1a39"><a href="https://www.pazarlamaakademisi.com" target="_blank" style="color:#0e1a39;text-decoration:none">www.pazarlamaakademisi.com</a></td>
        </tr>
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA"><table width="200" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
              </tr>
            </table></td>
        </tr>
		<tr>
			<td bgcolor="#F9FAFA">&nbsp;</td>
		</tr>
		<tr>
			<td bgcolor="#F9FAFA">&nbsp;</td>
		</tr>
    </table></td>
  </tr>
</table>
</body>
</html>';
    return $response;
  }

  protected function getMailBodyV2(): string {

    $items = $this->getSessionsForAddressId();
    $values = [];
    foreach ($items as $item) {
      $values['adiniz_ve_soyadiniz'] = $item->field_adiniz_ve_soyadiniz[0]->value;

    }

    $this->initializeBasketSettings();
    $nodes = $this->basketsSessionNodes;
    $sumCount = 0;

    $count = array_count_values($this->basketsSessions);


    foreach ($count as $basketsSession) {
      $sumCount += $basketsSession;
    }


    foreach ($count as $basketsSession) {
      $sumCount += $basketsSession;
    }

    $response = '
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Pazarlama Akademisi</title>
</head>
<body style="margin:0; padding:0">
<table width="573" border="0" align="center" cellpadding="0" cellspacing="0" style="border-top:34px solid #F9FAFA;border-left:34px solid #F9FAFA;border-right:34px solid #F9FAFA">
  <tr>
    <td align="left" valign="top"><table width="573" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="left" valin="top">
                        <img src="https://dvsd0f8pn5yuv.cloudfront.net/images/PA_Logo.svg" alt="Pazarlama Akademisi" width="290" height="93" border="0" style="display:block; border:none"/>

          </td>
          <td align="right" valin="bottom"><table width="165" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td align="left" valign="top" width="40">
                </td>
                <td align="left" valign="middle" width="125" style="font-family:Arial,sans-serif;font-size:14px;line-height:14px;font-weight:bold;color:#0e1a39"></td>
              </tr>
            </table></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td align="center" valign="top"><table width="500" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="left" valign="top" style="border-top:2px solid #e3e4e9">&nbsp;</td>
        </tr>
        <tr>
          <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:20px;font-weight:normal;color:#0e1a39">Merhaba <strong>' . $values["adiniz_ve_soyadiniz"] . '</strong>,<br />
            <br />
           Satın almış olduğunuz atölye/kurs bilgileri aşağıdaki gibidir.</td>
        </tr>
        <tr>
          <td height="30">&nbsp;</td>
        </tr>
        <tr>
          <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:24px;line-height:24px;font-weight:bold;color:#0e1a39">Detaylar</td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top"><table width="500" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td align="center" valign="top" bgcolor="#f9fafa" style="background-color:#f9fafa"><table width="450" border="0" cellpadding="0" cellspacing="0">

';
    $itemCount = 0;
    $sumPrice = 0;
    $selectStock = '';
    foreach ($nodes as $item) {
      if ($sumCount == 0) {
        continue;
      }
      $image = $item->field_field_image->entity;
      if (is_null($image)) {
        continue;
      }
      $imageUrl = str_replace('public://', '/sites/default/files/', $image->getFileUri());
      $title = $item->getTitle();
      $id = $item->nid->value;
      $body = $item->body->value;
      $field_item_old_number = $item->field_item_old_number->value;
      $field_item_current_number = $item->field_item_current_number->value;
      $field_teacher = $item->field_item_user_name->value;
      $field_zoom_link = $item->field_zoom_link->value;

      $SameItemCounts = [];
      if ($item->field_item_stock_and_date != NULL) {

        foreach ($item->field_item_stock_and_date as $it) {
          if (in_array($item->id() . " + " . $it->value, $this->basketsSessions)) {
            $SameItemCounts[] = $item->id() . " + " . $it->value;

          }
        }
      }


      $response .= '  <tr>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:22px;line-height:35px;font-weight:bold;color:#0e1a39;text-decoration:underline">
                <a href="https://pazarlamaakademisi.com/urun-detay-talep?itemId=' . $id . '">' . $title . '</a> </td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:18px;line-height:30px;font-weight:bold;color:#0e1a39">' . $field_teacher . '</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:20px;font-weight:normal;color:#0e1a39">' . $body . '</td>
              </tr>
              <tr>
                <td style="border-bottom:2px solid #e5e7e9">&nbsp;</td>
              </tr>
              <tr>

                <td>&nbsp;</td>
              </tr>
              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:24px;font-weight:normal;color:#0e1a39"><strong>' . $selectStock . '</strong></td>
              </tr>
              <tr>
                <td height="10"></td>
              </tr>

              <tr>
                <td align="left" valign="top" style="font-family:Arial,sans-serif;font-size:16px;line-height:24px;font-weight:normal;color:#0e1a39">Link: <a href="' . $field_zoom_link . '" target="_blank" style="color:#0943db">' . $field_zoom_link . '</a></td>
              </tr>
              <tr>
                <td style="border-bottom:2px solid #e5e7e9">&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
              </tr>

              <tr>
                <td>&nbsp;</td>
              </tr>
           ';


    }

    $response .= ' </table></td>
        </tr>
      </table></td>
  </tr>
  <tr>
    <td height="34">&nbsp;</td>
  </tr>
  <tr>
    <td align="left" valign="top" bgcolor="#f9fafa" style="background-color:#f9fafa"><table width="573" border="0" cellpadding="0" cellspacing="0">
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA" style="font-family:Arial,sans-serif;font-size:16px;line-height:22px;font-weight:normal;color:#0e1a39">Sorularınız varsa bu e-postayı yanıtlayın veya<br />
            <a href="mailto:destek@pazarlamaakademisi.com" style="color:#0e1a39;text-decoration:underline;font-weight:bold">destek@pazarlamaakademisi.com</a> adresinden bizimle<br />
            iletişime geçin.<br />
            <br />
            Pazarlama Akademisi Danışmanlık Hizmetleri A.Ş<br />
            Levent, Esentepe Mah. Büyükdere Cad. Ecza Sokak Safter Han<br />
            İş Merkezi, D:No:6 Kat:1 14394</td>
        </tr>
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA" style="font-family:Arial,sans-serif;font-size:16px;line-height:22px;font-weight:bold;color:#0e1a39"><a href="https://www.pazarlamaakademisi.com" target="_blank" style="color:#0e1a39;text-decoration:none">www.pazarlamaakademisi.com</a></td>
        </tr>
        <tr>
          <td bgcolor="#F9FAFA">&nbsp;</td>
        </tr>
        <tr>
          <td align="center" valign="top" bgcolor="#F9FAFA"><table width="200" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                 </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                 </a></td>
                <td align="center" valign="top" width="50"><a href="#" target="_blank">
                </a></td>
              </tr>
            </table></td>
        </tr>
		<tr>
			<td bgcolor="#F9FAFA">&nbsp;</td>
		</tr>
		<tr>
			<td bgcolor="#F9FAFA">&nbsp;</td>
		</tr>
    </table></td>
  </tr>
</table>
</body>
</html>';
    return $response;
  }

  protected function setBaskets($baskets) {
    //@todo add new baskets here
  }


  private function convertArraytoIds($basketsSessions): array {
    $uniq = array_unique($basketsSessions);
    $ids = [];
    foreach ($uniq as $item) {
      $item = explode(' + ', $item);
      if ($item[0] == 9 or $item[0] == NULL) {
        continue;
      }
      $ids[] = $item[0];
    }

    return $ids;
  }

  private function convertArrayToIdsWithoutUniques($basketsSessions): array {
    $uniq = $basketsSessions;
    $ids = [];
    foreach ($uniq as $item) {
      $item = explode(' + ', $item);
      if ($item[0] == 9 or $item[0] == NULL) {
        continue;
      }
      $ids[] = $item[0];
    }

    return $ids;
  }

  protected function setIyzicoForm() {
    $sumCount = 0;

    //@todo need stock con
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "https") . "://$_SERVER[HTTP_HOST]";
    if ($this->getSessionsForAddressId() == NULL) {
      header("Location: $actual_link" . "/payments_v2");
      die();
    }
    $items = $this->getSessionsForAddressId();
    $values = [];


    $tempstore = \Drupal::service('tempstore.private')->get('address');
    $tStore = $tempstore->get('user_address_id');
    foreach ($items as $item) {
      $values['tc'] = $item->field_t_c_kimlik_no[0]->value;
      $values['sehir'] = $item->field_sehir[0]->value;
      $values['message_key'] = $item->field_session_id[0]->value;
      $values['adiniz_ve_soyadiniz'] = $item->field_adiniz_ve_soyadiniz[0]->value;
      $values['cep_telefonunuz'] = $item->field_cep_telefonunuz[0]->value;
      $values['apartman_daire_vb_istege_bagli_'] = $item->field_adres[0]->value;
      $values['ulke'] = $item->field_county[0]->value;
      $values['posta_kodu'] = "34400";
      $values['e_mail_adresiniz'] = $item->field_e_mail_adresiniz[0]->value;
    }
    $this->initializeBasketSettings();
    $nodes = $this->basketsSessionNodes;


    $count = array_count_values($this->basketsSessions);


    foreach ($count as $basketsSession) {
      $sumCount += $basketsSession;
    }


    $itemCount = 0;
    $sumPrice = 0;
    $ids = 0;
    $basketItems = [];

    foreach ($nodes as $item) {
      if ($sumCount == 0) {
        continue;
      }
      $image = $item->field_field_image->entity;
      if (is_null($image)) {
        continue;
      }
      $title = $item->getTitle();
      $id = $item->nid->value;
      $field_item_current_number = $item->field_item_current_number->value;


      $SameItemCounts = [];
      if ($item->field_item_stock_and_date != NULL) {

        foreach ($item->field_item_stock_and_date as $it) {
          if (in_array($item->id() . " + " . $it->value, $this->basketsSessions)) {
            $SameItemCounts[] = $item->id() . " + " . $it->value;

          }
        }

        $i = 0;
        foreach ($SameItemCounts as $key => $SameItemCount) {
          $ids++;

          $i++;
          ${'BasketItem' . $ids} = new BasketItem();


          $stockMax = explode(' + ', $SameItemCount);

          $stockMax[0] = explode(';', $stockMax[1]);


          $itemCount += $count[$SameItemCount];

          $itemPrice = ($field_item_current_number * $count[$SameItemCount]);
          $sumPrice += $itemPrice;
          ${'BasketItem' . $ids}->setId("B" . $id . "-" . $i . "-" . $ids);
          ${'BasketItem' . $ids}->setName($title);
          ${'BasketItem' . $ids}->setItemType(BasketItemType::PHYSICAL);
          ${'BasketItem' . $ids}->setPrice($itemPrice);
          ${'BasketItem' . $ids}->setCategory1("Eğitim");
          ${'BasketItem' . $ids}->setCategory2("Sağlık");
          $basketItems[] = ${'BasketItem' . $ids};
        }
      }

    }
    $this->formInitialRequest = new CreateCheckoutFormInitializeRequest();
    $this->formInitialRequest->setLocale(Locale::TR);
    $this->formInitialRequest->setConversationId($tStore);
    $this->formInitialRequest->setPrice($sumPrice);
    $this->formInitialRequest->setPaidPrice($sumPrice);
    $this->formInitialRequest->setCurrency(Currency::TL);
    $this->formInitialRequest->setBasketId("B" . $tStore);
    $this->formInitialRequest->setPaymentGroup(PaymentGroup::PRODUCT);
    $this->formInitialRequest->setCallbackUrl($actual_link . "/callback");
    $this->formInitialRequest->setEnabledInstallments([1, 2, 3, 6, 9]);

    $buyer = new Buyer();
    $buyer->setId($values['message_key']);
    $buyer->setName($values['adiniz_ve_soyadiniz']);
    $buyer->setSurname($values['adiniz_ve_soyadiniz']);
    $buyer->setGsmNumber($values['cep_telefonunuz']);
    $buyer->setEmail($values['e_mail_adresiniz']);
    $buyer->setIdentityNumber($values['tc']);

    $buyer->setLastLoginDate(date('Y-m-d H:i:s'));
    $buyer->setRegistrationDate(date('Y-m-d H:i:s'));
    $buyer->setRegistrationAddress($values['apartman_daire_vb_istege_bagli_']);
    $buyer->setIp($this->getIPAddress());
    $buyer->setCity($values['sehir']);
    $buyer->setCountry($values['ulke']);
    $buyer->setZipCode($values['posta_kodu']);

    $this->formInitialRequest->setBuyer($buyer);
    $shippingAddress = new Address();
    $shippingAddress->setContactName($values['adiniz_ve_soyadiniz']);
    $shippingAddress->setCity($values['sehir']);
    $shippingAddress->setCountry($values['ulke']);
    $shippingAddress->setAddress($values['apartman_daire_vb_istege_bagli_']);
    $shippingAddress->setZipCode($values['posta_kodu']);
    $this->formInitialRequest->setShippingAddress($shippingAddress);

    $billingAddress = new Address();
    $billingAddress->setContactName($values['adiniz_ve_soyadiniz']);
    $billingAddress->setCity($values['sehir']);
    $billingAddress->setCountry($values['ulke']);
    $billingAddress->setAddress($values['apartman_daire_vb_istege_bagli_']);
    $billingAddress->setZipCode($values['posta_kodu']);
    $this->formInitialRequest->setBillingAddress($billingAddress);
    $this->formInitialRequest->setBasketItems($basketItems);

    $checkoutFormInitialize = CheckoutFormInitialize::create($this->formInitialRequest, $this->options);
    if ($checkoutFormInitialize->getStatus() == "failure") {
      dd($checkoutFormInitialize);
    }

    $this->content = $checkoutFormInitialize->getCheckoutFormContent();


  }

  protected function getSessionsForAddressId(): array {

    $tempstore = \Drupal::service('tempstore.private')->get('address');
    $nIds = $this->paymentStorage->getQuery()
      ->condition('field_session_id', $tempstore->get('user_address_id'))
      ->sort('id', 'desc')
      ->execute();
    if ($nIds == NULL) {
      $nIds = $this->paymentStorage->getQuery()
        ->sort('id', 'desc')
        ->pager(1)
        ->execute();
    }
    return $this->paymentStorage->loadMultiple($nIds);

  }

  private function getIPAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }


  /***
   * maillerin 2 tanesi burda
   */

  protected function basketDetailMail() {

    //
    // A very simple PHP example that sends a HTTP POST to a remote site
    //

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://k4gkjspta9.execute-api.eu-north-1.amazonaws.com/Test/sendemailtransactional");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,
      '{
  "ToAddresses": [
    "levendclk@gmail.com"
  ],
  "Subject": "Test Subject",
  "MailBody": "hello",
  "Sender": "Pazarlama Akademisi<noreply@pazarlamaakademisi.com>"
}');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    $server_output = curl_exec($ch);

    curl_close($ch);

    // Further processing ...
    if ($server_output == "OK") {
      // dd('happens');
    }
    else {
      //  dd($server_output);
    }


  }

  protected function dateToRequestedDate($dates): string {
    $separate = explode(' {', $dates);
    $date = str_replace(' - ', '-', $separate[0]);


    $betweenDates = str_replace('}', '', $separate[1]);
    $betweenDates = str_replace('-', '/', $betweenDates);

    $date = date('/d M Y - H:i', strtotime($date));
    $dateExploded = explode('/', $date);
    $dateExtra = explode(' ', $dateExploded[1]);
    setlocale(LC_TIME, 'tr_TR');
    $date = str_replace('/' . $dateExtra[0], $betweenDates, $date);
    return $this->convertMonthToTurkishCharacter($date);
  }

  public function convertMonthToTurkishCharacter($date): string {
    $months = [
      'January' => 'Ocak',
      'February' => 'Şubat',
      'March' => 'Mart',
      'April' => 'Nisan',
      'May' => 'Mayıs',
      'June' => 'Haziran',
      'July' => 'Temmuz',
      'August' => 'Ağustos',
      'September' => 'Eylül',
      'October' => 'Ekim',
      'November' => 'Kasım',
      'December' => 'Aralık',
      'Monday' => 'Pazartesi',
      'Tuesday' => 'Salı',
      'Wednesday' => 'Çarşamba',
      'Thursday' => 'Perşembe',
      'Friday' => 'Cuma',
      'Saturday' => 'Cumartesi',
      'Sunday' => 'Pazar',
      'Jan' => 'Oca',
      'Feb' => 'Şub',
      'Mar' => 'Mar',
      'Apr' => 'Nis',
      'Jun' => 'Haz',
      'Jul' => 'Tem',
      'Aug' => 'Ağu',
      'Sep' => 'Eyl',
      'Oct' => 'Eki',
      'Nov' => 'Kas',
      'Dec' => 'Ara',

    ];
    return strtr($date, $months);
  }

}

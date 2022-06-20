<?php

namespace Drupal\iyzico\Services\Baskets;


use Iyzipay\Options;

/**
 *
 */
class BasketService extends BasketServiceAbstract {

  public function getBaskets(): string {
    return parent::getBaskets(); // TODO: Change the autogenerated stub
  }

  /**
   * @return string
   */
  public function getBasketsId(): string {
    return $this->basketsId;
  }

  /**
   * @return string
   */
  public function getContent(): string {
    $this->initializeBasketSettings();
    $this->setIyzicoForm();
    return $this->content;
  }

  public function initializeBasketSettings() {
    parent::initializeBasketSettings(); // TODO: Change the autogenerated stub
  }

  /**
   * @return mixed
   */
  public function getBasketsSessions() {
    return $this->basketsSessions;
  }

  /**
   * @param mixed $basketsSessions
   */
  public function setBasketsSessions($basketsSessions): void {
    $this->basketsSessions[] = $basketsSessions;
  }

  public function basketDetailMail() {
    parent::basketDetailMail();
  }

  public function dateToRequestedDate($date): string {
    return parent::dateToRequestedDate($date); // TODO: Change the autogenerated stub
  }

  public function getMailBody(): string {
    return parent::getMailBody(); // TODO: Change the autogenerated stub
  }

  /**
   * @return \Iyzipay\Options
   */
  public function getOptions(): Options {
    return $this->options;
  }

  /**
   * @return array
   */
  public function getBasketsSessionsIds(): array {
    $this->initializeBasketSettingsWithoutUnique();
    return $this->basketsSessionsIds;
  }

  public function getSessionsForAddressId(): array {
    return parent::getSessionsForAddressId(); // TODO: Change the autogenerated stub
  }

  public function getMailBodyV2(): string {
    return parent::getMailBodyV2(); // TODO: Change the autogenerated stub
  }

}

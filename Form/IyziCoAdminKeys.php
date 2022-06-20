<?php

namespace Drupal\iyzico\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IyziCoAdminKeys.
 */
class IyziCoAdminKeys extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'iyzico.iyzicoadminkeys',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'iyzi_co_admin_keys';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('iyzico.iyzicoadminkeys');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('api key'),
      '#default_value' => $config->get('api_key'),
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('secret key'),
      '#default_value' => $config->get('secret_key'),
    ];
    $form['api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('api url'),
      '#default_value' => $config->get('api_url'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('iyzico.iyzicoadminkeys')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('api_url', $form_state->getValue('api_url'))
      ->save();
  }

}

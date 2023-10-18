<?php

namespace Drupal\currency_center\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\currency_center\CurrencyCenterFixerio;
use Drupal\currency_center\CurrencyCenterRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for a Currency Center Fixer.io integration.
 *
 * Here you can configure API key.
 */
class CurrencyCenterSettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'currency_center.settings';

  /**
   * CurrencyCenterFixerio object.
   *
   * @var \Drupal\currency_center\CurrencyCenterFixerio
   */
  private $client;

  /**
   * CurrencyCenterRepository object.
   *
   * @var \Drupal\currency_center\CurrencyCenterRepository
   */
  private $repo;

  /**
   * CurrencyCenterSettingsForm constructor.
   *
   * @param \Drupal\currency_center\CurrencyCenterFixerio $currencyCenterFixerio
   *   CurrencyCenterFixerio service.
   * @param \Drupal\currency_center\CurrencyCenterRepository $currencyCenterRepository
   *   CurrencyCenterRepository service.
   */
  public function __construct(CurrencyCenterFixerio $currencyCenterFixerio,
  CurrencyCenterRepository $currencyCenterRepository) {
    $this->client = $currencyCenterFixerio;
    $this->repo = $currencyCenterRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('currency_center.fixerio'),
      $container->get('currency_center.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'currency_center_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fixer.io API key'),
      '#required' => TRUE,
      '#default_value' => $config->get('apikey'),
      '#description' => $this->t('A valid Fixer.io API key here. For more information please visit <a href=":docs">documentation page</a>.', [
        ':docs' => 'https://fixer.io/documentation',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // If somehow API key is missing throw an error.
    if (empty($config->get('apikey')) && empty($form_state->getValue('apikey'))) {
      $form_state->setError($form['apikey'], $this->t('No API key is provided.'));
    }

    // Try to validate API key.
    if ($config->get('apikey') !== $form_state->getValue('apikey')) {

      // Try to check API key by call to Supported Symbols Endpoint.
      $data = $this->client->getCodes($form_state->getValue('apikey'));

      // If successfull not to loose a chance to save some data if first time.
      if (empty($config->get('apikey')) && !empty($data['success'])) {

        // Get currency list based on Currency list setting.
        $selected_list_data = array_intersect_key($data['symbols'], array_flip($config->get('curlist')));
        // Prepare data to save.
        foreach ($selected_list_data as $currencyCode => $currencyName) {

          $entry = [
            'curcode' => $currencyCode,
            'curname' => $currencyName,
          ];

          // Try to insert DB records.
          $return = $this->repo->insert($entry);
          if ($return) {
            $this->messenger()->addMessage($this->t('Created currency entry for @curname', ['@curname' => $entry['curname']]));
          }
        }
      }

      elseif (empty($data['success'])) {
        $form_state->setError($form['apikey'], $this->t(':error_code -> :error_type',
        [':error_code' => $data['error']['code'], ':error_type' => $data['error']['type']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    if ($config->get('apikey') !== $form_state->getValue('apikey')) {
      // Make a call to get data from Fixer.io.
      $data = $this->client->getRates($form_state->getValue('apikey'));

      // If call was successfull prepare data to store.
      if (!empty($data['success'])) {
        foreach ($data['rates'] as $currencyCode => $currencyRate) {
          $entry = [
            'curcode' => $currencyCode,
            'rate' => $currencyRate,
            'rate_timestamp' => $data['timestamp'],
          ];
          // Try to update DB records.
          $return = $this->repo->update($entry);
          if ($return) {
            $this->messenger()->addMessage($this->t('Updated currency entry for @curcode', ['@curcode' => $entry['curcode']]));
          }
        }
      }
    }

    // Save API key.
    $config->set('apikey', $form_state->getValue('apikey'))->save();
    $this->messenger()->addStatus($this->t('API key was successfully saved'));

    // Redirect to Currency Center Page.
    $form_state->setRedirect('currency_center.main');
    parent::submitForm($form, $form_state);
  }

}

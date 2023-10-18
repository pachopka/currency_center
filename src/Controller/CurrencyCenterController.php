<?php

namespace Drupal\currency_center\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\currency_center\CurrencyCenterRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Currency Center page route.
 */
class CurrencyCenterController extends ControllerBase {

  /**
   * CurrencyCenterRepository object.
   *
   * @var \Drupal\currency_center\CurrencyCenterRepository
   */
  private $repo;

  /**
   * CurrencyCenterController constructor.
   *
   * @param \Drupal\currency_center\CurrencyCenterRepository $currencyCenterRepository
   *   CurrencyCenterRepository service.
   */
  public function __construct(CurrencyCenterRepository $currencyCenterRepository) {
    $this->repo = $currencyCenterRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('currency_center.repository')
    );
  }

  /**
   * Building the list of currencies as a table.
   *
   * @throws \RuntimeException
   */
  public function tableRender() {

    $build = [];

    $config = $this->config('currency_center.settings');

    if (empty($config->get('apikey'))) {
      $this->messenger()->addWarning($this->t('You need to <a href="@url">add a API key</a> to be able to store and convert currencies via Fixer.io Integration.', [
        '@url' => Url::fromRoute('currency_center.settings_form')->toString(),
      ]));
      return $build['intro'] = [
        '#markup' => 'Unable to establish connection to Fixer.io service.',
      ];
    }

    // Try to load currencies data via DB service.
    $data = $this->repo->load();
    if (!empty($data)) {

      // Build table with currencies.
      $build['currency_table'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Currency'),
          $this->t('Code'),
          $this->t('Rate'),
          $this->t('Last update'),
        ],
      ];

      foreach ($data as $currency => $currency_data) {
        $build['currency_table'][$currency]['name']['#plain_text'] = $currency_data->curname;
        $build['currency_table'][$currency]['code']['#plain_text'] = $currency_data->curcode;
        $build['currency_table'][$currency]['rate']['#plain_text'] = sprintf('%g', $currency_data->rate);
        $build['currency_table'][$currency]['timestamp']['#plain_text'] = DrupalDateTime::createFromTimestamp($currency_data->rate_timestamp)->__toString();
      }
    }

    else {
      $build['intro'] = [
        '#markup' => 'Unable to load currencies.',
      ];
    }

    return $build;
  }

}

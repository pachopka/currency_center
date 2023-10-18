<?php

namespace Drupal\currency_center;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\currency_center\CurrencyCenterRepository;

/**
 * Convert service.
 */
class CurrencyCenterConvert {

  /**
   * List of avaialble currencies.
   *
   * @var array
   */
  protected $curList;

  /**
   * Base Currency.
   *
   * @var string
   */
  protected $baseCur;

  /**
   * Repository service.
   *
   * @var \Drupal\currency_center\CurrencyCenterRepository
   */
  protected $repo;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The constructor.
   *
   * @param \Drupal\currency_center\CurrencyCenterRepository $currencyCenterRepository
   *   CurrencyCenterRepository service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   */
  public function __construct(CurrencyCenterRepository $currencyCenterRepository, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {

    $this->curList = [];
    $this->repo = $currencyCenterRepository;
    $this->logger = $logger;

    $config = $config_factory->get('currency_center.settings');
    $this->baseCur = $config->get('base');

    // Try to load currencies data via DB service.
    $data = $this->repo->load();
    if (!empty($data)) {
      foreach ($data as $currency_data) {
        $curList[$currency_data->curcode] = $currency_data->rate;
      }
      $this->curList = $curList;
    }
  }

  /**
   * Converts $value of $fromCur to $toCur.
   *
   * @param float $value
   *   Value to convert.
   * @param string $fromCur
   *   From currency.
   * @param string $toCur
   *   To currency.
   *
   * @return float|FALSE
   *   Converted value.
   */
  public function convert(float $value, string $fromCur, string $toCur) {

    switch (true) {
      // Return FALSE in case we got negative number as a value.
      case $value < 0:
        return FALSE;
        break;
      // Return 0 in case we got 0 as a value.
      case $value === 0:
        return 0;
        break;
      // Proceed if come calculations need.
      default:
        // Checking if we have rates for the currencies.
        $validFromCur = array_key_exists($fromCur, $this->curList) ? $fromCur : FALSE;
        $validToCur = array_key_exists($toCur, $this->curList) ? $toCur : FALSE;

        if (empty($this->curList) || empty($validFromCur) || empty($validToCur)) {
          $this->logger->get('currency_center')->error('There are no data or selected currencies are not avaialble.');

          return FALSE;
        }
        else {
          // Convert the value.
          return $this->convertLogic($value,$validFromCur, $validToCur);
      }
    }
  }

  /**
   * Actual converter logic.
   *
   * @param float $value
   *   Value to convert.
   * @param string $fromCur
   *   Initial currency.
   * @param string $toCur
   *   Resulting currency.
   *
   * @return float
   *   Converted value.
   */
  private function convertLogic(float $value, string $fromCur, string $toCur) {

    // Convertation process.
    switch (true) {

      case $this->baseCur === $toCur:
        $result = bcdiv($value, $this->curList[$fromCur], 12);
        return sprintf('%0.12F', $result);
        break;

      case $this->baseCur === $fromCur:
        $result = bcmul($value, $this->curList[$toCur], 12);
        return sprintf('%0.12F', $result);
        break;

      default:
        $cross_amount = bcdiv($value, $this->curList[$fromCur], 12);
        $result = bcmul(sprintf('%0.12F', $cross_amount), $this->curList[$toCur], 12);
        return sprintf('%0.12F', $result);
        break;
    }
  }

}

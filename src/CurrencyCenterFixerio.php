<?php

namespace Drupal\currency_center;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Response;


/**
 * Fixer.io Interaction service.
 */
class CurrencyCenterFixerio {

  /**
   * The client used to send HTTP requests.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * The headers used when sending HTTP request.
   * 
   * @var array
   */
  protected $clientHeaders = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
  ];

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;


  /**
   * Fixer.io API Key.
   *
   * @var string
   */
  protected $apikey;

  /**
   * The URL of the remote REST server.
   *
   * @var string
   */
  protected $codeUrl;

  /**
   * The URL of the Endpoint: Latest Rates.
   *
   * @var string
   */
  protected $rateUrl;

  /**
   * List of avaialble currencies.
   *
   * @var string
   */
  protected $curList;

  /**
   * Base Currency.
   *
   * @var string
   */
  protected $baseCur;

  /**
   * The constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The HTTP client.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config Factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   */
  public function __construct(ClientInterface $client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger) {

    $this->client = $client;
    $this->logger = $logger;

    $config = $config_factory->get('currency_center.settings');

    $this->codeUrl = $config->get('curcode_url');
    $this->rateUrl = $config->get('currate_url');
    $this->curList = implode(', ', $config->get('curlist'));
    $this->baseCur = $config->get('base');
  }

  /**
   * Get latest currency rates.
   *
   * @param string $apikey
   *   Fixer.io API key.
   *
   * @return mixed
   *   JSON formatted string with the data from the remote server.
   *
   * @throws \InvalidArgumentException.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getRates(string $apikey) {

    try {
      // Send the GET request to the API endpoint.
      // Default base Currency is EUR.
      $response = $this->client->request('GET', 
      $this->rateUrl . '?access_key=' . $apikey .
      '& symbols=' . $this->curList .
      '& base=' . $this->baseCur, 
      ['headers' => $this->clientHeaders]);

      // Check the response status code.
      if ($response->getStatusCode() === 200) {
        return Json::decode($response->getBody()->getContents());
      }

      else {
        return new Response('An error occurred while getting Currency rates.',
        $response->getStatusCode());
      }
    }

    catch (\Exception $e) {
      $this->logger->get('currency_center')->error('Failed call to Endpoint: Latest Rates.
    Message = %message', ['%message' => $e->getMessage()]);
    }
  }

  /**
   * Get list of available currencies.
   *
   * @param string $apikey
   *   Fixer.io API key.
   *
   * @return mixed
   *   JSON formatted string with the data from the remote server.
   *
   * @throws \InvalidArgumentException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getCodes(string $apikey) {

    try {
      // Send the GET request to the API endpoint.
      $response = $this->client->request('GET',
      $this->codeUrl . '?access_key=' . $apikey, 
      ['headers' => $this->clientHeaders]);

      // Check the response status code.
      if ($response->getStatusCode() === 200) {
        return Json::decode($response->getBody()->getContents());
      }

      else {
        return new Response('An error occurred while getting list of currencies.',
        $response->getStatusCode());
      }
    }

    catch (\Exception $e) {
      $this->logger->get('currency_center')->error('Failed call to Endpoint: Latest Rates.
    Message = %message', ['%message' => $e->getMessage()]);
    }
  }

}

<?php

namespace Drupal\currency_center;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Repository service for database-related helper methods.
 */
class CurrencyCenterRepository {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   */
  public function __construct(Connection $connection, LoggerChannelFactoryInterface $logger) {
    $this->connection = $connection;
    $this->logger = $logger;
  }

  /**
   * Save an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('currency_center')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->get('currency_center')->error('Currencies insert failed.
      Message = %message', ['%message' => $e->getMessage()]);
    }
    return $return_value ?? NULL;
  }

  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      $count = $this->connection->update('currency_center')
        ->fields($entry)
        ->condition('curcode', $entry['curcode'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->logger->get('currency_center')->error('Currencies update failed.
      Message = %message', ['%message' => $e->getMessage()]);
    }
    return $count ?? 0;
  }

  /**
   * Read from the database.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function load() {

    // Read all the fields from the table.
    $select = $this->connection
      ->select('currency_center')
      ->fields('currency_center');

    // Return the result as object.
    return $select->execute()->fetchAll();
  }

}

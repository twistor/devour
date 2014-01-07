<?php

/**
 * @file
 * Contains \Devour\Processor\Pdo.
 */

namespace Devour\Processor;

use Aura\Sql_Schema\ColumnFactory;
use Devour\ConfigurableInterface;
use Devour\Exception\ConfigurationException;
use Devour\Row\RowInterface;

/**
 * A simple PDO database processor.
 */
class Pdo extends ProcessorBase implements ConfigurableInterface {

  /**
   * The database connection.
   *
   * @var \PDO
   */
  protected $connection;

  /**
   * The database table.
   *
   * @var string
   */
  protected $table;

  /**
   * The default values.
   *
   * @var array
   */
  protected $defaults;

  /**
   * The columns belonging to this table.
   *
   * @var array
   */
  protected $columns;

  /**
   * The columns that are unique.
   *
   * @var array
   */
  protected $uniqueColumns;

  /**
   * The prepared statement for inserting items.
   *
   * @var \PDOStatement
   */
  protected $saveStatement;

  /**
   * The prepared statement for finding existing items.
   *
   * @var \PDOStatement
   */
  protected $uniqueStatement;

  /**
   * Constructs a new Pdo object.
   *
   * @param \PDO $connection
   *   A PDO database connection.
   */
  public function __construct(\PDO $connection, $table, array $unique_columns = NULL) {
    $this->connection = $connection;
    $this->table = $this->escapeTable($table);
    $this->columns = $this->getColumns();

    $this->saveStatement = $this->prepareSaveStatement();
    $this->defaults = array_fill_keys($this->columns, NULL);

    if ($unique_columns) {
      $this->uniqueColumns = array_combine($unique_columns, $unique_columns);
      $this->uniqueStatement = $this->prepareUniqueStatement();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function fromConfiguration(array $configuration) {
    foreach (array('dsn', 'table') as $field) {
      if (empty($configuration[$field])) {
        throw new ConfigurationException(sprintf('The field "%s" is required.', $field));
      }
    }

    $configuration += array('username' => NULL, 'password' => NULL, 'unique' => NULL);
    $connection = new \PDO($configuration['dsn'], $configuration['username'], $configuration['password']);

    return new static($connection, $configuration['table'], $configuration['unique']);
  }

  /**
   * {@inheritdoc}
   */
  protected function processRow(RowInterface $row) {

    $item = array();

    foreach ($this->columns as $field) {
      $item[$field] = $row->get($field);
    }

    if ($this->uniqueColumns && $this->itemIsUnique($item)) {
      return;
    }

    $this->prepare($item);

    $this->save($item);
  }

  /**
   * Prepares an item for saving.
   */
  protected function prepare(array &$item) {
    $item += $this->defaults;
  }

  /**
   * Saves an item.
   */
  protected function save(array $item) {
    $this->saveStatement->execute($item);
  }

  protected function itemIsUnique(array $item) {
    $unique = array_intersect_key($item, $this->uniqueColumns);
    if ($result = $this->uniqueStatement->execute($unique)) {
      return (bool) $this->uniqueStatement->fetch();
    }
  }

  /**
   * Builds the prepared statement for inserting new rows.
   */
  protected function prepareSaveStatement() {
    $fields = implode(',', $this->columns);

    $placeholders = array();
    foreach ($this->columns as $column) {
      $placeholders[] = ':' . $column;
    }
    $placeholders = implode(',', $placeholders);

    // Prepare our statement.
    return $this->connection->prepare("INSERT INTO {$this->table} ($fields) VALUES ($placeholders)");
  }

  /**
   * Builds the prepared statement for finding existing rows.
   */
  protected function prepareUniqueStatement() {
    $clauses = array();
    foreach ($this->uniqueColumns as $column) {
      $clauses[] = "$column = :$column";
    }

    $clause = implode(' AND ', $clauses);

    // Prepare our statement.
    return $this->connection->prepare("SELECT 1 FROM {$this->table} WHERE $clause LIMIT 1");
  }

  /**
   * Escapes a table name string.
   *
   * Force all table names to be strictly alphanumeric-plus-underscore.
   *
   * @param string $table
   *   The table name.
   *
   * @return string
   *   The sanitized table name string.
   */
  protected function escapeTable($table) {
    return preg_replace('/[^A-Za-z0-9_.]+/', '', $table);
  }

  /**
   * Returns the column names of the table.
   */
  protected function getColumns() {
    $driver = $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);

    // Calculate the driver class. Why don't they do this for us?
    $class = '\\Aura\\Sql_Schema\\' . ucfirst($driver) . 'Schema';
    $schema = new $class($this->connection, new ColumnFactory());
    return array_keys($schema->fetchTableCols($this->table));
  }

}

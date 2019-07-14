<?php

namespace Odan\Database;

use PDO;
use PDOStatement;

/**
 * Insert Query.
 *
 * https://dev.mysql.com/doc/refman/5.7/en/insert.html
 */
final class InsertQuery implements QueryInterface
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Quoter
     */
    private $quoter;

    /**
     * @var string Table name
     */
    private $table;

    /**
     * @var array Value list
     */
    private $values;

    /**
     * @var array Assignment list
     */
    private $duplicateValues;

    /**
     * @var string Priority modifier
     */
    private $priority;

    /**
     * Errors that occur while executing the INSERT statement are ignored.
     *
     * @var string Ignore modifier
     */
    private $ignore;

    /**
     * Constructor.
     *
     * @param Connection $connection The pdo connection
     */
    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
        $this->quoter = $connection->getQuoter();
    }

    /**
     * Table name.
     *
     * @param string $table Table name
     *
     * @return self The self instance
     */
    public function into(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Priority modifier.
     *
     * @return self The self instance
     */
    public function lowPriority(): self
    {
        $this->priority = 'LOW_PRIORITY';

        return $this;
    }

    /**
     * Priority modifier.
     *
     * @return self The self instance
     */
    public function delayed(): self
    {
        $this->priority = 'DELAYED';

        return $this;
    }

    /**
     * Priority modifier.
     *
     * @return self The self instance
     */
    public function highPriority(): self
    {
        $this->priority = 'HIGH_PRIORITY';

        return $this;
    }

    /**
     * Ignore errors modifier.
     *
     * @return self The self instance
     */
    public function ignore(): self
    {
        $this->ignore = 'IGNORE';

        return $this;
    }

    /**
     * On Duplicate Key Update.
     *
     * @param array $values Value list
     *
     * @return self The self instance
     */
    public function onDuplicateKeyUpdate(array $values): self
    {
        $this->duplicateValues = $values;

        return $this;
    }

    /**
     * Execute.
     *
     * @return bool Success
     */
    public function execute(): bool
    {
        return $this->prepare()->execute();
    }

    /**
     * Prepare statement.
     *
     * @return PDOStatement The pdo statement
     */
    public function prepare(): PDOStatement
    {
        return $this->pdo->prepare($this->build());
    }

    /**
     * Build a SQL string.
     *
     * @return string The SQL string
     */
    public function build(): string
    {
        $table = $this->quoter->quoteName($this->table);

        $insert = 'INSERT';
        if (!empty($this->priority)) {
            $insert .= ' ' . $this->priority;
        }
        if (!empty($this->ignore)) {
            $insert .= ' ' . $this->ignore;
        }

        if (array_key_exists(0, $this->values)) {
            // multiple rows
            $result = sprintf('%s INTO %s (%s) VALUES', $insert, $table, $this->quoter->quoteFields($this->values[0]));
            foreach ($this->values as $key => $row) {
                $result .= sprintf('%s(%s)', ($key > 0) ? ',' : '', $this->quoter->quoteBulkValues($row));
            }
        } else {
            // single row
            $result = sprintf('%s INTO %s SET %s', $insert, $table, $this->quoter->quoteSetValues($this->values));
        }

        if ($this->duplicateValues) {
            $values = $this->quoter->quoteSetValues($this->duplicateValues);
            $result .= sprintf(' ON DUPLICATE KEY UPDATE %s', $values);
        }
        $result .= ';';

        return $result;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @param string $name [optional] Name of the sequence object from which the ID should be returned
     *
     * @return string Last inserted Id
     */
    public function lastInsertId(string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * Insert new row(s) and return new Id.
     *
     * @param array $values Values
     *
     * @return string Last inserted Id
     */
    public function insertGetId(array $values): string
    {
        $stmt = $this->set($values)->prepare();
        $stmt->execute();

        return $this->pdo->lastInsertId();
    }

    /**
     * Value list.
     *
     * @param array $values Value list
     *
     * @return self The self instance
     */
    public function set(array $values): self
    {
        $this->values = $values;

        return $this;
    }
}

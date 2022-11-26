<?php

namespace Selective\Database;

use PDO;
use PDOStatement;
use RuntimeException;

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
    private PDO $pdo;

    /**
     * @var Quoter
     */
    private Quoter $quoter;

    /**
     * @var string Table name
     */
    private string $table;

    /**
     * @var array Value list
     */
    private array $values;

    /**
     * @var array Assignment list
     */
    private array $duplicateValues = [];

    /**
     * @var string Priority modifier
     */
    private string $priority = '';

    /**
     * Errors that occur while executing the INSERT statement are ignored.
     *
     * @var string Ignore modifier
     */
    private string $ignore = '';

    /**
     * The constructor.
     *
     * @param Connection $connection The database connection
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
     * @throws RuntimeException
     *
     * @return PDOStatement The pdo statement
     */
    public function prepare(): PDOStatement
    {
        $statement = $this->pdo->prepare($this->build());

        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('The database statement could not be prepared.');
        }

        return $statement;
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
     * @param string|null $name The name of the sequence object from which the ID should be returned. Optional.
     *
     * @return string Last inserted Id
     */
    public function lastInsertId(string $name = null): string
    {
        if ($name === null) {
            return $this->pdo->lastInsertId() ?: '0';
        }

        return $this->pdo->lastInsertId($name) ?: '0';
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

        return $this->lastInsertId();
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

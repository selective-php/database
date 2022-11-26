<?php

namespace Selective\Database;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Update Query.
 *
 * https://dev.mysql.com/doc/refman/5.7/en/update.html
 */
final class UpdateQuery implements QueryInterface
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
     * @var Condition Where conditions
     */
    private $condition;

    /**
     * @var array Order by
     */
    private $orderBy = [];

    /**
     * @var int|null Limit
     */
    private $limit = null;

    /**
     * Constructor.
     *
     * @param Connection $connection The connection
     */
    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
        $this->quoter = $connection->getQuoter();
        $this->condition = new Condition($connection, $this);
    }

    /**
     * Priority modifier.
     *
     * @return self
     */
    public function lowPriority(): self
    {
        $this->priority = 'LOW_PRIORITY';

        return $this;
    }

    /**
     * Ignore errors modifier.
     *
     * @return self
     */
    public function ignore(): self
    {
        $this->ignore = 'IGNORE';

        return $this;
    }

    /**
     * Table name.
     *
     * @param string $table Table name
     *
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Values (key value).
     *
     * @param array $values The values
     *
     * @return self
     */
    public function set(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Where AND condition.
     *
     * @param array ...$conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function where(...$conditions): self
    {
        $this->condition->where($conditions);

        return $this;
    }

    /**
     * Where OR condition.
     *
     * @param array ...$conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function orWhere(...$conditions): self
    {
        $this->condition->orWhere($conditions);

        return $this;
    }

    /**
     * Order by.
     *
     * @param array ...$fields Column name(s)
     *
     * @return self
     */
    public function orderBy(...$fields): self
    {
        $this->orderBy = $fields;

        return $this;
    }

    /**
     * Limit the number of rows returned.
     *
     * @param int $rowCount Row count
     *
     * @return self
     */
    public function limit(int $rowCount): self
    {
        $this->limit = $rowCount;

        return $this;
    }

    /**
     * Incrementing or decrementing the value of a given column.
     *
     * @param string $column The column to modify
     * @param int $amount The amount by which the column should be incremented [optional]
     *
     * @return self
     */
    public function increment(string $column, int $amount = 1): self
    {
        $this->values[$column] = new RawExp(
            $this->quoter->quoteName($column) .
            '+' .
            $this->quoter->quoteValue($amount)
        );

        return $this;
    }

    /**
     * Decrementing the value of a given column.
     *
     * @param string $column The column to modify
     * @param int $amount The amount by which the column should be decrement [optional]
     *
     * @return self
     */
    public function decrement(string $column, int $amount = 1): self
    {
        $this->values[$column] = new RawExp(
            $this->quoter->quoteName($column) . '-' . $this->quoter->quoteValue($amount)
        );

        return $this;
    }

    /**
     * Executes a prepared statement.
     *
     * @return bool Success
     */
    public function execute(): bool
    {
        return $this->prepare()->execute();
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @throws RuntimeException
     *
     * @return PDOStatement The PDOStatement
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
     * @return string SQL string
     */
    public function build(): string
    {
        $sql = [];
        $sql = $this->getUpdateSql($sql);
        $sql = $this->getSetSql($sql);
        $sql = $this->condition->getWhereSql($sql);
        $sql = $this->getOrderBySql($sql);
        $sql = $this->getLimitSql($sql);

        return trim(implode(' ', $sql)) . ';';
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    private function getUpdateSql(array $sql): array
    {
        $update = 'UPDATE';
        if (!empty($this->priority)) {
            $update .= ' ' . $this->priority;
        }
        if (!empty($this->ignore)) {
            $update .= ' ' . $this->ignore;
        }
        $sql[] = $update . ' ' . $this->quoter->quoteName($this->table);

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    private function getSetSql(array $sql): array
    {
        // single row
        $sql[] = 'SET ' . $this->quoter->quoteSetValues($this->values);

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    private function getOrderBySql(array $sql): array
    {
        if (empty($this->orderBy)) {
            return $sql;
        }
        $sql[] = 'ORDER BY ' . implode(', ', $this->quoter->quoteByFields($this->orderBy));

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    private function getLimitSql(array $sql): array
    {
        if (!isset($this->limit)) {
            return $sql;
        }
        $sql[] = sprintf('LIMIT %s', $this->limit);

        return $sql;
    }
}

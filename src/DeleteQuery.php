<?php

namespace Selective\Database;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Delete Query.
 *
 * @see https://dev.mysql.com/doc/refman/5.7/en/delete.html
 */
final class DeleteQuery implements QueryInterface
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
     * @var string Priority modifier
     */
    private string $priority;

    /**
     * Errors that occur while executing the DELETE statement are ignored.
     *
     * @var string Ignore modifier
     */
    private string $ignore;

    /**
     * @var string Ignore modifier
     */
    private string $quick;

    /**
     * @var Condition Where conditions
     */
    private Condition $condition;

    /**
     * @var array Order by
     */
    private array $orderBy = [];

    /**
     * @var int Row count
     */
    private int $limit;

    /**
     * @var bool Truncate
     */
    private bool $truncate = false;

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
     * If you specify LOW_PRIORITY, the server delays execution of the
     * DELETE until no other clients are reading from the table.
     *
     * This affects only storage engines that use only table-level
     * locking (such as MyISAM, MEMORY, and MERGE).
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
     * Quick modifier.
     *
     * For MyISAM tables, if you use the QUICK modifier,
     * the storage engine does not merge index leaves during delete,
     * which may speed up some kinds of delete operations.
     *
     * @return self
     */
    public function quick(): self
    {
        $this->quick = 'QUICK';

        return $this;
    }

    /**
     * Table name.
     *
     * @param string $table Table name
     *
     * @return self
     */
    public function from(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Where AND condition.
     *
     * @param array ...$conditions The condition (field, comparison, value)
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
     * Truncate the entire table.
     *
     * @return self
     */
    public function truncate(): self
    {
        $this->truncate = true;

        return $this;
    }

    /**
     * Executes a prepared statement.
     *
     * @return bool
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
     * @return PDOStatement
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
        if ($this->truncate === true) {
            return $this->getTruncateSql();
        }
        $sql = [];
        $sql = $this->getDeleteSql($sql);
        $sql = $this->condition->getWhereSql($sql);
        $sql = $this->getOrderBySql($sql);
        $sql = $this->getLimitSql($sql);

        return trim(implode(' ', $sql)) . ';';
    }

    /**
     * Get sql.
     *
     * @return string The sql
     */
    private function getTruncateSql(): string
    {
        return 'TRUNCATE TABLE ' . $this->quoter->quoteName($this->table) . ';';
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    private function getDeleteSql(array $sql): array
    {
        $delete = 'DELETE';
        if (!empty($this->priority)) {
            $delete .= ' ' . $this->priority;
        }
        if (!empty($this->quick)) {
            $delete .= ' ' . $this->quick;
        }
        if (!empty($this->ignore)) {
            $delete .= ' ' . $this->ignore;
        }
        $sql[] = $delete . ' FROM ' . $this->quoter->quoteName($this->table);

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

<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class DeleteQuery
 *
 * @see https://dev.mysql.com/doc/refman/5.7/en/delete.html
 */
class DeleteQuery implements QueryInterface
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * @var Quoter
     */
    protected $quoter;

    /**
     * @var string Table name
     */
    protected $table;

    /**
     * @var string Priority modifier
     */
    protected $priority;

    /**
     * Errors that occur while executing the DELETE statement are ignored
     *
     * @var string Ignore modifier
     */
    protected $ignore;

    /**
     * @var string Ignore modifier
     */
    protected $quick;

    /**
     * @var Condition Where conditions
     */
    protected $condition;

    /**
     * @var array Order by
     */
    protected $orderBy = [];

    /**
     * @var int Row count
     */
    protected $limit;

    /**
     * Constructor.
     *
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
        $this->quoter = $pdo->getQuoter();
        $this->condition = new Condition($pdo, $this);
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
     * Ignore errors modifier
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
     * Table name
     *
     * @param string $table Table name
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
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
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
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
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
     * @param array $fields Column name(s)
     * @return self
     */
    public function orderBy(array $fields): self
    {
        $this->orderBy = $fields;
        return $this;
    }

    /**
     * Limit the number of rows returned.
     *
     * @param int $rowCount Row count
     * @return self
     */
    public function limit(int $rowCount): self
    {
        $this->limit = $rowCount;
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
     * Prepares a statement for execution and returns a statement object
     *
     * @return PDOStatement
     */
    public function prepare(): PDOStatement
    {
        return $this->pdo->prepare($this->build());
    }

    /**
     * Build a SQL string.
     *
     * @return string SQL string
     */
    public function build(): string
    {
        $sql = [];
        $sql = $this->getDeleteSql($sql);
        $sql = $this->condition->getWhereSql($sql);
        $sql = $this->getOrderBySql($sql);
        $sql = $this->getLimitSql($sql);
        $result = trim(implode(" ", $sql)) . ';';
        return $result;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    public function getDeleteSql(array $sql)
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
     * @param array $sql
     * @return array
     */
    protected function getOrderBySql(array $sql): array
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
     * @param $sql
     * @return array
     */
    protected function getLimitSql(array $sql): array
    {
        if (!isset($this->limit)) {
            return $sql;
        }
        $sql[] = sprintf('LIMIT %s', (int)$this->limit);
        return $sql;
    }
}

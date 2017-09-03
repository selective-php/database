<?php

namespace Odan\Database;

use PDOStatement;

/**
 * UpdateQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/update.html
 */
class UpdateQuery implements QueryInterface
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
     * @var array Value list
     */
    protected $values;

    /**
     * @var array Assignment list
     */
    protected $duplicateValues;

    /**
     * @var string Priority modifier
     */
    protected $priority;

    /**
     * Errors that occur while executing the INSERT statement are ignored
     *
     * @var string Ignore modifier
     */
    protected $ignore;

    /**
     * @var Condition Where conditions
     */
    protected $condition;

    /**
     * @var array Order by
     */
    protected $orderBy = [];

    /**
     * @var int Limit
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
     * Table name
     *
     * @param string $table Table name
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
     * @param array $values
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
        $sql = $this->getUpdateSql($sql);
        $sql = $this->getSetSql($sql);
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
    public function getUpdateSql(array $sql)
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
     * @param array $sql
     * @return array
     */
    public function getSetSql(array $sql):array
    {
        // single row
        $sql[] =  'SET ' . $this->quoter->quoteSetValues($this->values);
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

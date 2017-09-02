<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class DeleteQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/delete.html
 *
 * @todo Add flags [LOW_PRIORITY] [QUICK] [IGNORE]
 */
class DeleteQuery
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * @var string Table name
     */
    protected $table;

    /**
     * @var array Conditions
     */
    protected $where = [];

    /**
     * @var array Order by
     */
    protected $orderBy = [];

    /**
     * @var int|array Limit
     */
    protected $limit;

    /**
     * constructor.
     *
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    public function where(...$conditions)
    {
        $this->where[] = $conditions;
        return $this;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        return $this->prepare()->execute();
    }

    /**
     * @return PDOStatement
     */
    public function prepare(): PDOStatement
    {
        return $this->pdo->prepare($this->build());
    }

    /**
     * @return string SQL string
     */
    public function build(): string
    {
        // @todo
        return 'SELECT 1';
    }
}

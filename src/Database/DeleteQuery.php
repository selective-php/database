<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class DeleteQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/delete.html
 */
class DeleteQuery
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    protected $table;
    protected $where = [];
    protected $orderBy = [];
    protected $limit;

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

    // @todo Add flags
    // LOW_PRIORITY] [QUICK] [IGNORE

    /**
     * @return bool
     */
    public function execute()
    {
        return $this->getStatement()->execute();
    }

    /**
     * @return PDOStatement
     */
    public function getStatement()
    {
        return $this->pdo->prepare($this->getSql());
    }

    /**
     * @return string SQL string
     */
    public function getSql()
    {
        return 'SELECT 1';
    }
}

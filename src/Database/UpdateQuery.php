<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class UpdateQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/update.html
 */
class UpdateQuery
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    protected $table;
    protected $values;
    protected $where = [];
    protected $orderBy = [];
    protected $limit;

    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function values(array $values)
    {
        $this->values = $values;
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

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

    public function table($table): self
    {
        $this->table = $table;
        return $this;
    }

    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    public function where(...$conditions): self
    {
        $this->where[] = $conditions;
        return $this;
    }

    public function orderBy($orderBy): self
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function limit($limit): self
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
    public function build()
    {
        // @todo
        return 'SELECT 1';
    }
}

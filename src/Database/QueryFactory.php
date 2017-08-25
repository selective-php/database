<?php

namespace Odan\Database;

use PDO;

class QueryFactory
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * QueryFactory constructor.
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @return SelectQuery
     */
    public function select()
    {
        return new SelectQuery($this->pdo);
    }

    /**
     * @return InsertQuery
     */
    public function insert()
    {
        return new InsertQuery($this->pdo);
    }

    /**
     * @return UpdateQuery
     */
    public function update()
    {
        return new UpdateQuery($this->pdo);
    }

    /**
     * @return DeleteQuery
     */
    public function delete()
    {
        return new DeleteQuery($this->pdo);
    }
}
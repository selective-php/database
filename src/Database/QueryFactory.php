<?php

namespace Odan\Database;

class QueryFactory
{
    /**
     * @var Connection
     */
    protected $pdo;

    /**
     * QueryFactory constructor.
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
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

    public function fun()
    {
        return null;
    }
}

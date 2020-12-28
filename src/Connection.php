<?php

namespace Selective\Database;

use PDO;

/**
 * Database connection.
 */
final class Connection
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
     * The constructor.
     *
     * @param PDO $pdo The PDO instance
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->quoter = new Quoter($this);
    }

    /**
     * @return SelectQuery
     */
    public function select(): SelectQuery
    {
        return new SelectQuery($this);
    }

    /**
     * @return InsertQuery
     */
    public function insert(): InsertQuery
    {
        return new InsertQuery($this);
    }

    /**
     * @return UpdateQuery
     */
    public function update(): UpdateQuery
    {
        return new UpdateQuery($this);
    }

    /**
     * @return DeleteQuery
     */
    public function delete(): DeleteQuery
    {
        return new DeleteQuery($this);
    }

    /**
     * Get PDO.
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get quoter.
     *
     * @return Quoter
     */
    public function getQuoter(): Quoter
    {
        return $this->quoter;
    }
}

<?php

namespace Odan\Database;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use PDO;
use PDOStatement;

class Table
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var QueryFactory
     */
    protected $query;

    /**
     * @var string
     */
    protected $driver;

    public function __construct(Connection $db, QueryFactory $queryFactory)
    {
        $this->db = $db;
        $this->query = $queryFactory;
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @return QueryFactory
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Select|\Aura\SqlQuery\Common\SelectInterface|\Aura\SqlQuery\AbstractQuery
     */
    public function newSelect()
    {
        return $this->getQuery()->newSelect();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Delete|\Aura\SqlQuery\Common\DeleteInterface|\Aura\SqlQuery\AbstractQuery
     */
    public function newDelete()
    {
        return $this->getQuery()->newDelete();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Insert|\Aura\SqlQuery\Common\InsertInterface|\Aura\SqlQuery\AbstractQuery
     */
    public function newInsert()
    {
        return $this->getQuery()->newInsert();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Update|\Aura\SqlQuery\Common\UpdateInterface|\Aura\SqlQuery\AbstractQuery
     */
    public function newUpdate()
    {
        return $this->getQuery()->newUpdate();
    }

    /**
     * @param $table
     * @param $row
     * @return PDOStatement
     */
    public function insertRow($table, $row)
    {
        $insert = $this->newInsert()->into($table)->cols($row);
        $stmt = $this->db->executeQuery($insert);
        return $stmt;
    }

    /**
     * @param $table
     * @param $rows
     * @return int
     */
    public function insertRows($table, $rows)
    {
        $result = 0;
        foreach ($rows as $row) {
            $this->insertRow($table, $row);
            $result++;
        }
        return $result;
    }

    /**
     * Update row
     *
     * <code>
     * $db->updateRow('table_name', array('name' => 'bar'), array('id' => 42));
     * </code>
     *
     * @param string $tableName table
     * @param array $fields fields
     * @param array $conditions conditions
     * @return PDOStatement
     */
    public function updateRow($tableName, array $fields, array $conditions = array())
    {
        $update = $this->newUpdate()->table($tableName)->cols($fields);
        foreach ($conditions as $key => $value) {
            $update->where("$key = ?", $value);
        }
        return $this->db->executeQuery($update);
    }

    /**
     * Delete row by condition
     *
     * <code>
     * $db->deleteRow('table_name', array('col2' => 42, 'col5' => 3));
     * </code>
     *
     * @param string $tableName table
     * @param array $conditions condition
     * @return PDOStatement
     */
    public function deleteRow($tableName, array $conditions = array())
    {
        $delete = $this->newDelete()->from($tableName);
        foreach ($conditions as $key => $value) {
            $delete->where("$key = ?", $value);
        }
        return $this->db->executeQuery($delete);
    }
}

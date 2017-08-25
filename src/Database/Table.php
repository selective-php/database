<?php

namespace Odan\Database;

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

    public function __construct(Connection $db, QueryFactory $queryFactory)
    {
        $this->db = $db;
        $this->query = $queryFactory;
    }

    /**
     * @return QueryFactory
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return SelectQuery
     */
    public function select()
    {
        return $this->getQuery()->select();
    }

    /**
     * @return DeleteQuery
     */
    public function delete()
    {
        return $this->getQuery()->delete();
    }

    /**
     * @return InsertQuery
     */
    public function insert()
    {
        return $this->getQuery()->insert();
    }

    /**
     * @return UpdateQuery
     */
    public function update()
    {
        return $this->getQuery()->update();
    }

    /**
     * @param $table
     * @param $row
     * @return PDOStatement
     */
    public function insertRow($table, $row)
    {
        $statement = $this->insert()->into($table)->values($row);
        $statement->execute();
        return $statement;
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
     * @param array $values values
     * @param array $conditions conditions
     * @return PDOStatement
     */
    public function updateRow($tableName, array $values, array $conditions = array())
    {
        $update = $this->update()->table($tableName)->values($values);
        foreach ($conditions as $key => $value) {
            $update->where($key, '=', $value);
        }
        $statement = $update->getStatement();
        $statement->execute();
        return $statement;
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
        $delete = $this->delete()->from($tableName);
        foreach ($conditions as $key => $value) {
            $delete->where($key, '=', $value);
        }
        $statement = $delete->getStatement();
        $statement->execute();
        return $statement;
    }
}

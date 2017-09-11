<?php

namespace Odan\Database;

/**
 * Class Repository
 */
abstract class Repository implements RepositoryInterface
{

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var string Table name
     */
    protected $table;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return SelectQuery
     */
    public function select()
    {
        return $this->db->select()->from($this->table);
    }

    /**
     * Insert a row or multiple rows.
     *
     * <code>
     * $db->insert(array('username' => 'admin'))->prepare()->execute();
     * </code>
     *
     * @param array $row
     * @return InsertQuery
     */
    public function insert(array $row): InsertQuery
    {
        return $this->db->insert()->into($this->table)->set($row);
    }

    /**
     * Update row
     *
     * <code>
     * $db->update(array('name' => 'bar'), array('id' => 42))->prepare()->execute();
     * </code>
     *
     * @param array $values values
     * @param array $conditions conditions
     * @return UpdateQuery
     */
    public function update(array $values, array $conditions = []): UpdateQuery
    {
        $update = $this->db->update()->table($this->table)->set($values);
        foreach ($conditions as $key => $value) {
            $update->where($key, '=', $value);
        }
        return $update;
    }

    /**
     * Delete row(s)
     *
     * <code>
     * $db->delete(array('col2' => 42, 'col5' => 3))->prepare()->execute();
     * </code>
     *
     * @param array $conditions condition
     * @return DeleteQuery
     */
    public function delete(array $conditions = []): DeleteQuery
    {
        $delete = $this->db->delete()->from($this->table);
        foreach ($conditions as $key => $value) {
            $delete->where($key, '=', $value);
        }
        return $delete;
    }
}

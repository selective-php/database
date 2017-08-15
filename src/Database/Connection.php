<?php

namespace Odan\Database;

use Aura\SqlQuery\QueryFactory;
use Aura\SqlQuery\QueryInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

class Connection extends PDO
{
    protected $driver;

    /**
     * @var QueryFactory
     */
    protected $query;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var Encryption
     */
    protected $encryption;

    /**
     * Connection constructor.
     *
     * @param $dsn
     * @param $username
     * @param $passwd
     * @param $options
     */
    public function __construct($dsn, $username, $passwd, $options)
    {
        parent::__construct($dsn, $username, $passwd, $options);
        $this->driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * @return QueryFactory
     */
    public function getQuery()
    {
        if ($this->query === null) {
            $this->query = new QueryFactory($this->driver);
        }
        return $this->query;
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Select|\Aura\SqlQuery\Common\SelectInterface
     */
    public function newSelect()
    {
        return $this->getQuery()->newSelect();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Delete|\Aura\SqlQuery\Common\DeleteInterface
     */
    public function newDelete()
    {
        return $this->getQuery()->newDelete();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Insert|\Aura\SqlQuery\Common\InsertInterface
     */
    public function newInsert()
    {
        return $this->getQuery()->newInsert();
    }

    /**
     * @return \Aura\SqlQuery\Mysql\Update|\Aura\SqlQuery\Common\UpdateInterface
     */
    public function newUpdate()
    {
        return $this->getQuery()->newUpdate();
    }

    /**
     * @return Schema
     */
    public function getSchema()
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this);
        }
        return $this->schema;
    }

    /**
     * Return Data object
     *
     * @return Encryption
     */
    public function getEncryption()
    {
        if ($this->encryption === null) {
            $this->encryption = new Encryption($this);
        }
        return $this->encryption;
    }

    /**
     * Returns connection status
     *
     * @return bool
     */
    public function ping()
    {
        try {
            $result = !empty($this->getAttribute(PDO::ATTR_CONNECTION_STATUS));
            return $result && !empty($this->query('SELECT 1;')->fetch());
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Escapes special characters in a string for use in an SQL statement
     *
     * @param mixed $value
     * @return string quoted string for use in a query
     */
    public function esc($value)
    {
        $quote = "'";

        // detect null value
        if ($value === null) {
            return 'NULL';
        }
        $value = $this->quote($value, PDO::PARAM_STR);
        $value = $quote . substr($value, 1, -1) . $quote;
        return $value;
    }

    /**
     * Escape identifier (column, table) with backtick
     *
     * @see: http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
     *
     * @param mixed $value
     * @return string identifier escaped string
     * @throws InvalidArgumentException
     */
    public function ident($value)
    {
        if ($value === null || strlen(trim($value)) == 0) {
            throw new InvalidArgumentException('Value cannot be null or empty');
        }
        $quote = "`";
        $value = $this->quote($value, PDO::PARAM_STR);
        $value = substr($value, 1, -1);
        if (strpos($value, '.') !== false) {
            $values = explode('.', $value);
            $value = $quote . implode($quote . '.' . $quote, $values) . $quote;
        } else {
            $value = $quote . $value . $quote;
        }
        return $value;
    }

    /**
     * @param QueryInterface $query
     * @return PDOStatement
     */
    public function prepareQuery(QueryInterface $query)
    {
        $stmt = $this->prepare($query->getStatement());
        foreach ($query->getBindValues() as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        return $stmt;
    }

    public function executeQuery(QueryInterface $query)
    {
        $stmt = $this->prepare($query->getStatement());
        $status = $stmt->execute($query->getBindValues());
        return $stmt;
    }

    /**
     * Retrieving a list of column values
     *
     * sample:
     * $lists = $db->queryValues('SELECT id FROM table;', 'id');
     *
     * @param string $sql
     * @param string $key
     * @return array
     */
    public function queryValues($sql, $key)
    {
        $result = array();
        $statement = $this->query($sql);
        foreach ($statement->fetchAll() as $row) {
            $result[] = $row[$key];
        }
        return $result;
    }

    /**
     * Retrieve only the given column of the first result row
     *
     * @param string $sql
     * @param string $column
     * @param mixed $default
     * @return string
     */
    public function queryValue($sql, $column, $default = null)
    {
        $result = $default;
        $row = $this->query($sql)->fetch();
        if (!empty($row)) {
            $result = $row[$column];
        }
        return $result;
    }

    /**
     * Map query result by column as new index
     *
     * <code>
     * $rows = $db->queryMapColumn('SELECT * FROM table;', 'id');
     * </code>
     *
     * @param string $sql
     * @param string $key Column name to map as index
     * @return array
     */
    public function queryMapColumn($sql, $key)
    {
        $result = array();
        $rows = $this->query($sql)->fetchAll();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $result[$row[$key]] = $row;
            }
        }
        return $result;
    }

    /**
     * @param $table
     * @param $row
     * @return PDOStatement
     */
    public function insertRow($table, $row)
    {
        $insert = $this->newInsert()->into($table)->cols($row);
        $stmt = $this->executeQuery($insert);
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
        return $this->executeQuery($update);
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
        return $this->executeQuery($delete);
    }
}

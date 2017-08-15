<?php

namespace Odan\Database;

use Aura\SqlQuery\QueryInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;

class Connection extends PDO
{

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
    public function quoteValue($value)
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
    public function quoteName($value)
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
        $statement = $this->prepare($query->getStatement());
        foreach ($query->getBindValues() as $param => $value) {
            $statement->bindValue($param, $value);
        }
        return $statement;
    }

    /**
     * @param QueryInterface $query
     * @return PDOStatement
     */
    public function executeQuery(QueryInterface $query)
    {
        $statement = $this->prepare($query->getStatement());
        $statement->execute($query->getBindValues());
        return $statement;
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
}

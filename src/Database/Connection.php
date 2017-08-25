<?php

namespace Odan\Database;

use InvalidArgumentException;
use PDO;

class Connection extends PDO
{

    /**
     * Escapes special characters in a string for use in an SQL statement
     *
     * @param mixed $value
     * @return string quoted string for use in a query
     */
    public function quoteValue($value)
    {
        if ($value === null) {
            return 'NULL';
        }
        return $this->quote($value);
    }

    public function quoteArray($array)
    {
        if (!$array) {
            return [];
        }
        foreach ($array as $key => $value) {
            $array[$key] = $this->quoteValue($value);
        }
        return $array;
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
        while ($row = $statement->fetch()) {
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
        if ($row = $this->query($sql)->fetch()) {
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
        $statement = $this->query($sql);
        while ($row = $statement->fetch()) {
            $result[$row[$key]] = $row;
        }
        return $result;
    }
}

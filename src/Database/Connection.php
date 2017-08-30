<?php

namespace Odan\Database;

use InvalidArgumentException;
use PDO;

class Connection extends PDO
{

    /**
     * Quotes a value for use in a query.
     *
     * @param mixed $value
     * @return string|false a quoted string
     */
    public function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        return $this->quote($value);
    }

    /**
     * Quote array values.
     *
     * @param array|null $array
     * @return array
     */
    public function quoteArray(?array $array): array
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
     * @param mixed $value Identifier name
     * @return string Quoted identifier
     * @throws InvalidArgumentException
     */
    public function quoteName($value): string
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
    public function queryValues($sql, $key): array
    {
        $result = [];
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
     * @return mixed|null
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

    /**
     * Return the correct pdo data type.
     *
     * @param mixed $value The value
     * @return int PDO::PARAM_*
     */
    public function getType($value)
    {
        switch (true) {
            case is_bool($value):
                $dataType = PDO::PARAM_BOOL;
                break;
            case is_int($value):
                $dataType = PDO::PARAM_INT;
                break;
            case is_null($value):
                $dataType = PDO::PARAM_NULL;
                break;
            default:
                $dataType = PDO::PARAM_STR;
        }
        return $dataType;
    }
}

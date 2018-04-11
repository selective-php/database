<?php

namespace Odan\Database;

use RuntimeException;

/**
 * Quoter.
 */
class Quoter
{
    /**
     * Connection.
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * Constructor.
     *
     * @param Connection $pdo
     */
    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Quote array values.
     *
     * @param array $array
     *
     * @return array
     */
    public function quoteArray(array $array): array
    {
        if (empty($array)) {
            return [];
        }
        foreach ($array as $key => $value) {
            $array[$key] = $this->quoteValue($value);
        }

        return $array;
    }

    /**
     * Quotes a value for use in a query.
     *
     * @param mixed $value
     *
     * @throws RuntimeException
     *
     * @return string a quoted string
     */
    public function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        $result = $this->pdo->quote($value);

        if ($result === false) {
            throw new RuntimeException('The database driver does not support quoting in this way.');
        }

        return $result;
    }

    /**
     * Quote array of names.
     *
     * @param array $identifiers
     *
     * @return array
     */
    public function quoteNames(array $identifiers): array
    {
        foreach ($identifiers as $key => $identifier) {
            if ($identifier instanceof RawExp) {
                $identifiers[$key] = $identifier->getValue();
                continue;
            }
            $identifiers[$key] = $this->quoteName($identifier);
        }

        return $identifiers;
    }

    /**
     * Escape identifier (column, table) with backticks.
     *
     * @see: http://dev.mysql.com/doc/refman/5.0/en/identifiers.html
     *
     * @param string $identifier Identifier name
     *
     * @return string Quoted identifier
     */
    public function quoteName(string $identifier): string
    {
        $identifier = trim($identifier);
        $separators = [' AS ', ' ', '.'];
        foreach ($separators as $sep) {
            $pos = strripos($identifier, $sep);
            if ($pos) {
                return $this->quoteNameWithSeparator($identifier, $sep, $pos);
            }
        }

        return $this->quoteIdentifier($identifier);
    }

    /**
     * Quotes an identifier that has a separator.
     *
     * @param string $spec the identifier name to quote
     * @param string $sep the separator, typically a dot or space
     * @param int $pos the position of the separator
     *
     * @return string the quoted identifier name
     */
    protected function quoteNameWithSeparator(string $spec, string $sep, int $pos): string
    {
        $len = strlen($sep);
        $part1 = $this->quoteName(substr($spec, 0, $pos));
        $part2 = $this->quoteIdentifier(substr($spec, $pos + $len));

        return "{$part1}{$sep}{$part2}";
    }

    /**
     * Quotes an identifier name (table, index, etc); ignores empty values and
     * values of '*'.
     *
     * Escape backticks inside by doubling them
     * Enclose identifier in backticks
     *
     * After such formatting, it is safe to insert the $table variable into query.
     *
     * @param string $name the identifier name to quote
     *
     * @return string the quoted identifier name
     *
     * @see quoteName()
     */
    public function quoteIdentifier(string $name): string
    {
        $name = trim($name);
        if ($name == '*') {
            return $name;
        }

        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * Quote Set values.
     *
     * @param array $row A row
     *
     * @return string Sql string
     */
    public function quoteSetValues(array $row): string
    {
        $values = [];
        foreach ($row as $key => $value) {
            if ($value instanceof RawExp) {
                $values[] = $this->quoteName($key) . '=' . $value->getValue();
                continue;
            }
            $values[] = $this->quoteName($key) . '=' . $this->quoteValue($value);
        }

        return implode(', ', $values);
    }

    /**
     * Quote bulk values.
     *
     * @param array $row A row
     *
     * @return string Sql string
     */
    public function quoteBulkValues(array $row): string
    {
        $values = [];
        foreach ($row as $key => $value) {
            $values[] = $this->quoteValue($value);
        }

        return implode(',', $values);
    }

    /**
     * Quote fields values.
     *
     * @param array $row A row
     *
     * @return string Sql string
     */
    public function quoteFields(array $row): string
    {
        $fields = [];
        foreach (array_keys($row) as $field) {
            $fields[] = $this->quoteName($field);
        }

        return implode(', ', $fields);
    }

    /**
     * Get sql.
     *
     * @param array $identifiers
     *
     * @return array
     */
    public function quoteByFields(array $identifiers): array
    {
        foreach ($identifiers as $key => $identifier) {
            if ($identifier instanceof RawExp) {
                $identifiers[$key] = $identifier->getValue();
                continue;
            }
            // table.id ASC
            if (preg_match('/^([\w-\.]+)(\s)*(.*)$/', $identifier, $match)) {
                $identifiers[$key] = $this->quoteName($match[1]) . $match[2] . $match[3];
                continue;
            }
            $identifiers[$key] = $this->quoteName($identifier);
        }

        return $identifiers;
    }
}

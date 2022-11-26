<?php

namespace Selective\Database;

use PDO;
use RuntimeException;
use UnexpectedValueException;

use function array_key_first;

/**
 * Quoter.
 */
final class Quoter
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * The constructor.
     *
     * @param Connection $connection The connection instance
     */
    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
    }

    /**
     * Quote array values.
     *
     * @param array $array The values
     *
     * @return array The quoted values
     */
    public function quoteArray(array $array): array
    {
        if (empty($array)) {
            return [];
        }
        foreach ($array as $key => $value) {
            if ($value instanceof RawExp) {
                $array[$key] = $value->getValue();
                continue;
            }

            $array[$key] = $this->quoteValue($value);
        }

        return $array;
    }

    /**
     * Quotes a value for use in a query.
     *
     * @param mixed $value The value
     *
     * @throws RuntimeException
     *
     * @return string A quoted string
     */
    public function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        // @phpstan-ignore-next-line
        $result = $this->pdo->quote((string)$value);

        if (!is_string($result)) {
            throw new UnexpectedValueException('The database driver does not support quoting in this way.');
        }

        return $result;
    }

    /**
     * Quote array of names.
     *
     * @param array $identifiers The identifiers
     *
     * @return array The quoted identifiers
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
     * @see: http://dev.mysql.com/doc/refman/8.0/en/identifiers.html
     *
     * @param string|array $identifier Identifier name
     *
     * @return string Quoted identifier
     */
    public function quoteName($identifier): string
    {
        if (is_array($identifier)) {
            $key = (string)array_key_first($identifier);
            $value = $identifier[$key];

            if ($value instanceof RawExp) {
                return sprintf('%s AS %s', $value->getValue(), $this->quoteIdentifier($key));
            }

            return sprintf('%s AS %s', $this->quoteName($identifier[$key]), $this->quoteIdentifier($key));
        }

        $identifier = trim($identifier);
        $separators = ['.'];
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
     * @param string $spec The identifier name to quote
     * @param string $sep The separator, typically a dot or space
     * @param int $pos The position of the separator
     *
     * @return string The quoted identifier name
     */
    private function quoteNameWithSeparator(string $spec, string $sep, int $pos): string
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
     * @param string $name The identifier name to quote
     *
     * @return string The quoted identifier name
     *
     * @see quoteName()
     */
    public function quoteIdentifier(string $name): string
    {
        $name = trim($name);
        if ($name === '*') {
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
     * @param array $identifiers The identifiers
     *
     * @return array The quoted identifiers
     */
    public function quoteByFields(array $identifiers): array
    {
        foreach ($identifiers as $key => $identifier) {
            if ($identifier instanceof RawExp) {
                $identifiers[$key] = $identifier->getValue();
                continue;
            }
            // table.id ASC
            if (preg_match('/^([\w\-\.]+)(\s)*(.*)$/', $identifier, $match)) {
                $identifiers[$key] = $this->quoteName($match[1]) . $match[2] . $match[3];
                continue;
            }
            $identifiers[$key] = $this->quoteName($identifier);
        }

        return $identifiers;
    }
}

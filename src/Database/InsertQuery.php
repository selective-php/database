<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class InsertQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/insert.html
 */
class InsertQuery
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * @var string Table name
     */
    protected $table;

    /**
     * @var array Value list
     */
    protected $values;

    /**
     * @var array Assignment list
     */
    protected $duplicateValues;

    /**
     * @var string Priority modifier
     */
    protected $priority;

    /**
     * Errors that occur while executing the INSERT statement are ignored
     *
     * @var string Ignore modifier
     */
    protected $ignore;

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
     * Table name.
     *
     * @param string $table Table name
     * @return self
     */
    public function into(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Value list.
     *
     * @param array $values Value list
     * @return self
     */
    public function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    /**
     * Priority modifier.
     *
     * @return self
     */
    public function lowPriority(): self
    {
        $this->priority = 'LOW_PRIORITY';
        return $this;
    }

    /**
     * Priority modifier
     *
     * @return self
     */
    public function delayed(): self
    {
        $this->priority = 'DELAYED';
        return $this;
    }

    /**
     * Priority modifier
     *
     * @return self
     */
    public function heighPriority(): self
    {
        $this->priority = 'HIGH_PRIORITY';
        return $this;
    }

    /**
     * Ignore errors modifier
     *
     * @return self
     */
    public function ignore(): self
    {
        $this->ignore = 'IGNORE';
        return $this;
    }

    /**
     * On Duplicate Key Update.
     *
     * @param $values Value list
     * @return self
     */
    public function onDuplicateKeyUpdate($values): self
    {
        $this->duplicateValues = $values;
        return $this;
    }

    /**
     * Execute.
     *
     * @return bool Status
     */
    public function execute()
    {
        return $this->prepare()->execute();
    }

    /**
     * Prepare statement.
     *
     * @return PDOStatement
     */
    public function prepare()
    {
        return $this->pdo->prepare($this->build());
    }

    /**
     * Build SQL string.
     *
     * @return string SQL string
     */
    public function build()
    {
        $table = $this->pdo->quoteName($this->table);

        $insert = 'INSERT';
        if (!empty($this->priority)) {
            $insert .= ' ' . $this->priority;
        }
        if (!empty($this->ignore)) {
            $insert .= ' ' . $this->ignore;
        }

        if (array_key_exists(0, $this->values)) {
            // multiple rows
            // INSERT INTO tbl_name (a,b,c) VALUES(1,2,3),(4,5,6),(7,8,9)
            $result = sprintf("%s INTO %s (%s) VALUES", $insert, $table, $this->quoteFields($this->values[0]));
            foreach ($this->values as $key => $row) {
                $result .= sprintf("%s(%s)", ($key > 0) ? ',' : '', $this->quoteBulkValues($row));
            }
        } else {
            // single row
            $result = sprintf("%s INTO %s SET %s", $insert, $table, $this->quoteSetValues($this->values));
        }

        if ($this->duplicateValues) {
            $values = $this->quoteSetValues($this->duplicateValues);
            $result .= sprintf(' ON DUPLICATE KEY UPDATE %s', $values);
        }
        $result .= ';';

        return $result;
    }

    /**
     * Quote Set values.
     *
     * @param array $row A row
     * @return string Sql string
     */
    protected function quoteSetValues(array $row): string
    {
        $values = [];
        foreach ($row as $key => $value) {
            $values[] = $this->pdo->quoteName($key) . '=' . $this->pdo->quoteValue($value);
        }
        return implode(', ', $values);
    }

    /**
     * Quote bulk values.
     *
     * @param array $row A row
     * @return string Sql string
     */
    protected function quoteBulkValues(array $row): string
    {
        $values = [];
        foreach ($row as $key => $value) {
            $values[] = $this->pdo->quoteValue($value);
        }
        return implode(',', $values);
    }

    /**
     * Quote fields values.
     *
     * @param array $row A row
     * @return string Sql string
     */
    protected function quoteFields(array $row): string
    {
        $fields = [];
        foreach (array_keys($row) as $field) {
            $fields[] = $this->pdo->quoteName($field);
        }
        return implode(', ', $fields);
    }
}

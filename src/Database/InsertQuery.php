<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class InsertQuery
 *
 * https://dev.mysql.com/doc/refman/5.7/en/insert.html
 */
class InsertQuery implements QueryInterface
{
    /**
     * Connection
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * @var Quoter
     */
    protected $quoter;

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
        $this->quoter = $pdo->getQuoter();
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
    public function set(array $values): self
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
    public function highPriority(): self
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
     * @param array $values Value list
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
    public function execute(): bool
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
     * Build a SQL string.
     *
     * @return string SQL string
     */
    public function build(): string
    {
        $table = $this->quoter->quoteName($this->table);

        $insert = 'INSERT';
        if (!empty($this->priority)) {
            $insert .= ' ' . $this->priority;
        }
        if (!empty($this->ignore)) {
            $insert .= ' ' . $this->ignore;
        }

        if (array_key_exists(0, $this->values)) {
            // multiple rows
            $result = sprintf("%s INTO %s (%s) VALUES", $insert, $table, $this->quoter->quoteFields($this->values[0]));
            foreach ($this->values as $key => $row) {
                $result .= sprintf("%s(%s)", ($key > 0) ? ',' : '', $this->quoter->quoteBulkValues($row));
            }
        } else {
            // single row
            $result = sprintf("%s INTO %s SET %s", $insert, $table, $this->quoter->quoteSetValues($this->values));
        }

        if ($this->duplicateValues) {
            $values = $this->quoter->quoteSetValues($this->duplicateValues);
            $result .= sprintf(' ON DUPLICATE KEY UPDATE %s', $values);
        }
        $result .= ';';

        return $result;
    }
}

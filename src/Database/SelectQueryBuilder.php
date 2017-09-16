<?php

namespace Odan\Database;

use Closure;

/**
 * Class SelectQueryBuilder
 *
 * https://dev.mysql.com/doc/refman/5.7/en/select.html
 *
 * @todo Add support (and methods) for:
 * - UNION
 */
abstract class SelectQueryBuilder implements QueryInterface
{
    /**
     * PDO Connection
     *
     * @var Connection
     */
    protected $db;

    /**
     * @var Quoter
     */
    protected $quoter;

    protected $columns = [];
    protected $alias = null;
    protected $from = '';
    protected $join = [];

    /**
     * @var Condition Where conditions
     */
    protected $condition;

    protected $orderBy = [];
    protected $groupBy = [];
    protected $limit;
    protected $offset;
    protected $distinct = '';
    protected $calcFoundRows = '';
    protected $bufferResult = '';
    protected $resultSize = '';
    protected $straightJoin = '';
    protected $highPriority = '';

    /**
     * Constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->quoter = $db->getQuoter();
        $this->condition = new Condition($db, $this);
    }

    /**
     * Build a SQL string.
     *
     * @return string SQL string
     */
    public function build(): string
    {
        $sql = [];
        $sql = $this->getSelectSql($sql);
        $sql = $this->getColumnsSql($sql);
        $sql = $this->getFromSql($sql);
        $sql = $this->getJoinSql($sql);
        $sql = $this->condition->getWhereSql($sql);
        $sql = $this->getGroupBySql($sql);
        $sql = $this->condition->getHavingSql($sql);
        $sql = $this->getOrderBySql($sql);
        $sql = $this->getLimitSql($sql);
        $result = trim(implode(" ", $sql));
        $result = $this->getAliasSql($result);
        return $result;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getSelectSql(array $sql): array
    {
        $sql[] = trim('SELECT ' . trim(implode(' ', [
                $this->distinct,
                $this->highPriority,
                $this->straightJoin,
                $this->resultSize,
                $this->bufferResult,
                $this->calcFoundRows,
            ])));
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getColumnsSql(array $sql): array
    {
        if (empty($this->columns)) {
            $sql[] = '*';
            return $sql;
        }
        $columns = [];
        foreach ($this->columns as $key => $column) {
            if ($column instanceof Closure) {
                // Sub Select
                $query = new SelectQuery($this->db);
                $column($query);
                $column = new RawExp($query->build());
            }
            $columns[] = $column;
        }
        $sql[] = implode(',', $this->quoter->quoteNames($columns));
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getFromSql(array $sql): array
    {
        if (!empty($this->from)) {
            $sql[] = 'FROM ' . $this->quoter->quoteName($this->from);
        }
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param $sql
     * @return array
     */
    protected function getLimitSql(array $sql): array
    {
        if (!isset($this->limit)) {
            return $sql;
        }
        if (isset($this->offset)) {
            $sql[] = sprintf('LIMIT %s OFFSET %s', (float)$this->limit, (float)$this->offset);
        } else {
            $sql[] = sprintf('LIMIT %s', (float)$this->limit);
        }
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param string $sql
     * @return string $sql
     */
    protected function getAliasSql(string $sql): string
    {
        if (!isset($this->alias)) {
            return $sql;
        }
        return sprintf('(%s) AS %s', $sql, $this->quoter->quoteName($this->alias));
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getJoinSql(array $sql): array
    {
        if (empty($this->join)) {
            return $sql;
        }
        foreach ($this->join as $item) {
            list($type, $table, $leftField, $operator, $rightField) = $item;
            $joinType = strtoupper($type) . ' JOIN';
            $table = $this->quoter->quoteName($table);
            $leftField = $this->quoter->quoteName($leftField);
            $rightField = $this->quoter->quoteName($rightField);
            $sql[] = sprintf('%s %s ON %s %s %s', $joinType, $table, $leftField, $operator, $rightField);
        }
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getGroupBySql(array $sql): array
    {
        if (empty($this->groupBy)) {
            return $sql;
        }
        $sql[] = 'GROUP BY ' . implode(', ', $this->quoter->quoteByFields($this->groupBy));
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getOrderBySql(array $sql): array
    {
        if (empty($this->orderBy)) {
            return $sql;
        }
        $sql[] = 'ORDER BY ' . implode(', ', $this->quoter->quoteByFields($this->orderBy));
        return $sql;
    }
}

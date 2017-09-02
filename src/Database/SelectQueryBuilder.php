<?php

namespace Odan\Database;

/**
 * Class SelectQueryBuilder
 *
 * https://dev.mysql.com/doc/refman/5.7/en/select.html
 *
 * @todo Add support (and methods) for:
 * - distinctRow [DISTINCT | DISTINCTROW ]
 * - highPriority [HIGH_PRIORITY]
 * - straightJoin [STRAIGHT_JOIN]
 * - smallResult, bigResult [SQL_SMALL_RESULT] [SQL_BIG_RESULT]
 * - bufferResult [SQL_BUFFER_RESULT]
 * - calcFoundRows [SQL_CALC_FOUND_ROWS]
 * - UNION
 */
abstract class SelectQueryBuilder
{
    /**
     * PDO Connection
     *
     * @var Connection
     */
    protected $pdo;
    protected $columns = ['*'];
    protected $from = [];
    protected $join = [];
    protected $where = [];
    protected $orderBy = [];
    protected $groupBy = [];
    protected $limit;
    protected $offset;
    protected $having = [];
    protected $distinct = false;

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
        $sql = $this->getWhereSql($sql);
        $sql = $this->getGroupBySql($sql);
        $sql = $this->getHavingSql($sql);
        $sql = $this->getOrderBySql($sql);
        $sql = $this->getLimitSql($sql);
        $result = trim(implode(" ", $sql));
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
        $sql[] = 'SELECT' . (($this->distinct) ? ' DISTINCT' : '');
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
        if (!empty($this->columns)) {
            $sql[] = implode(',', $this->pdo->quoteNames($this->columns));
        }
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
            $sql[] = 'FROM ' . $this->pdo->quoteName($this->from);
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
     * @param $sql
     * @return array
     */
    protected function getWhereSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->where, 'WHERE');
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    protected function getHavingSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->having, 'HAVING');
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
            $table = $this->pdo->quoteName($table);
            $leftField = $this->pdo->quoteName($leftField);
            $rightField = $this->pdo->quoteName($rightField);
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
        $sql[] = 'GROUP BY ' . implode(', ', $this->quoteByFields($this->groupBy));
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
        $sql[] = 'ORDER BY ' . implode(', ', $this->quoteByFields($this->orderBy));
        return $sql;
    }

    /**
     * Adds to a clause through a closure, enclosing within parentheses.
     *
     * @param string $clause The clause to work with, typically 'where' or 'having'.
     * @param string $andor Add the condition using this operator, typically 'AND' or 'OR'.
     * @param callable $closure The closure that adds to the clause.
     * @return void
     */
    protected function addClauseCondClosure($clause, $andor, $closure)
    {
        // retain the prior set of conditions, and temporarily reset the clause
        // for the closure to work with (otherwise there will be an extraneous
        // opening AND/OR keyword)
        $set = $this->$clause;
        $this->$clause = [];
        // invoke the closure, which will re-populate the $this->$clause
        $closure($this);
        // are there new clause elements?
        if (!$this->$clause) {
            // no: restore the old ones, and done
            $this->$clause = $set;
            return;
        }

        // append an opening parenthesis to the prior set of conditions,
        // with AND/OR as needed ...
        if ($set) {
            $set[] = new RawExp(strtoupper($andor) . " (");
        } else {
            $set[] = new RawExp("(");
        }

        // append the new conditions to the set, with indenting
        $sql = [];
        $sql = $this->getConditionSql($sql, $this->$clause, '');
        foreach ($sql as $cond) {
            $set[] = new RawExp($cond);
        }
        $set[] = new RawExp(")");

        // ... then put the full set of conditions back into $this->$clause
        $this->$clause = $set;

        return;
    }

    /**
     * Comparison Functions and Operators
     *
     * https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html
     *
     * @param mixed $rightField
     * @param mixed $comparison
     * @return array
     */
    protected function getRightFieldValue($rightField, $comparison): array
    {
        if ($comparison == 'in' || $comparison == 'not in') {
            $rightField = '(' . implode(', ', $this->pdo->quoteArray((array)$rightField)) . ')';
        } elseif ($comparison == 'greatest' || $comparison == 'interval' || $comparison === 'strcmp') {
            $comparison = '= ' . $comparison;
            $rightField = '(' . implode(', ', $this->pdo->quoteArray((array)$rightField)) . ')';
        } elseif ($comparison === '=' && $rightField === null) {
            $comparison = 'IS';
            $rightField = $this->pdo->quoteValue($rightField);
        } elseif (($comparison === '<>' || $comparison === '!=') && $rightField === null) {
            $comparison = 'IS NOT';
            $rightField = $this->pdo->quoteValue($rightField);
        } elseif ($comparison === 'between' || $comparison === 'not between') {
            $between1 = $this->pdo->quoteValue($rightField[0]);
            $between2 = $this->pdo->quoteValue($rightField[1]);
            $rightField = sprintf('%s AND %s', $between1, $between2);
        } else {
            $rightField = $this->pdo->quoteValue($rightField);
        }
        return [$rightField, strtoupper($comparison)];
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param $where
     * @param $conditionType
     * @return array
     */
    protected function getConditionSql(array $sql, $where, $conditionType): array
    {
        if (empty($where)) {
            return $sql;
        }
        foreach ($where as $index => $item) {
            if ($item instanceof RawExp) {
                $sql[] = $item->getValue();
                continue;
            }
            list($type, $conditions) = $item;
            if (!$index) {
                $whereType = $conditionType;
            } else {
                $whereType = strtoupper($type);
            }
            if ($conditions[0] instanceof RawExp) {
                $sql[] = $whereType . ' ' . $conditions[0]->getValue();
                continue;
            }
            list($leftField, $operator, $rightField) = $conditions;
            $leftField = $this->pdo->quoteName($leftField);
            list($rightField, $operator) = $this->getRightFieldValue($rightField, $operator);

            $sql[] = sprintf('%s %s %s %s', $whereType, $leftField, $operator, $rightField);
        }
        return $sql;
    }

    /**
     * Get sql.
     *
     * @param $identifiers
     * @return array
     */
    protected function quoteByFields($identifiers): array
    {
        foreach ((array)$identifiers as $key => $identifier) {
            if ($identifier instanceof RawExp) {
                $identifiers[$key] = $identifier->getValue();
                continue;
            }
            // table.id ASC
            if (preg_match('/^([\w-\.]+)(\s)*(.*)$/', $identifier, $match)) {
                $identifiers[$key] = $this->pdo->quoteIdentifier($match[1]) . $match[2] . $match[3];
                continue;
            }
            $identifiers[$key] = $this->pdo->quoteName($identifier);
        }
        return $identifiers;
    }
}

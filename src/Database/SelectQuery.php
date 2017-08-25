<?php

namespace Odan\Database;

use PDO;
use PDOStatement;

class SelectQuery
{
    /**
     * PDO
     * @var PDO
     */
    protected $pdo;
    protected $columns = ['*'];
    protected $from = [];
    protected $join = [];
    protected $where = [];
    protected $orderBy = [];
    protected $groupBy = [];
    protected $limit = [];
    protected $having = [];
    protected $distinct = false;

    public function __construct(Connection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function columns($fields)
    {
        $this->columns = $fields;
        return $this;
    }

    public function from($table, $alias = null)
    {
        $this->from = [$table, $alias];
        return $this;
    }

    public function join($table, $leftField, $operator, $rightField)
    {
        $this->join[] = ['inner', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function leftJoin($table, $leftField, $operator, $rightField)
    {
        $this->join[] = ['left', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function crossJoin($table, $leftField, $operator, $rightField)
    {
        $this->join[] = ['cross', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function where(...$conditions)
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'AND', $conditions[0]);
            return $this;
        }
        $this->where[] = ['and', $conditions];
        return $this;
    }

    public function orWhere(...$conditions)
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'OR', $conditions[0]);
            return $this;
        }
        $this->where[] = ['or', $conditions];
        return $this;
    }

    /**
     *
     * Adds to a clause through a closure, enclosing within parentheses.
     *
     * @param string $clause The clause to work with, typically 'where' or
     * 'having'.
     *
     * @param string $andor Add the condition using this operator, typically
     * 'AND' or 'OR'.
     *
     * @param callable $closure The closure that adds to the clause.
     *
     * @return null
     *
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
            $set[] = new RawValue(strtoupper($andor) . " (");
        } else {
            $set[] = new RawValue("(");
        }

        // append the new conditions to the set, with indenting
        $sql = [];
        $sql = $this->getConditionSql($sql, $this->$clause, '');
        foreach ($sql as $cond) {
            $set[] = new RawValue($cond);
        }
        $set[] = new RawValue(")");

        // ... then put the full set of conditions back into $this->$clause
        $this->$clause = $set;
    }

    public function orderBy($fields)
    {
        $this->orderBy = $fields;
        return $this;
    }

    public function groupBy($fields)
    {
        $this->groupBy = $fields;
        return $this;
    }

    public function having(...$conditions)
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'AND', $conditions[0]);
            return $this;
        }
        $this->having[] = ['and', $conditions];
        return $this;
    }

    public function orHaving(...$conditions)
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'OR', $conditions[0]);
            return $this;
        }
        $this->having[] = ['or', $conditions];
        return $this;
    }

    public function limit($offset, $rowCount = null)
    {
        $this->limit = [$offset, $rowCount];
        return $this;
    }

    /**
     * @return PDOStatement
     */
    public function execute()
    {
        return $this->pdo->query($this->getSql());
    }

    /**
     * @return PDOStatement
     */
    public function getStatement()
    {
        return $this->pdo->prepare($this->getSql());
    }

    /**
     * @return string SQL string
     */
    public function getSql()
    {
        $sql = [];
        $sql[] = 'SELECT ' . (($this->distinct) ? 'DISTINCT ' : ' ');
        $sql[] = implode(',', (array)$this->columns) . ' ';
        $sql[] = 'FROM ' . implode(" ", (array)$this->from) . ' ';
        $sql = $this->getJoinSql($sql, $this->join);
        $sql = $this->getWhereSql($sql, $this->where);
        $sql = $this->getGroupBySql($sql, $this->groupBy);
        $sql = $this->getHavingSql($sql, $this->having);
        $sql = $this->getOrderBySql($sql, $this->orderBy);
        $sql = $this->getLimitSql($sql, $this->limit);
        $result = trim(implode("\n", $sql));
        return $result;
    }

    protected function getLimitSql($sql, $limit)
    {
        if (empty($limit)) {
            return $sql;
        }
        if (isset($limit[1])) {
            $sql[] = sprintf('LIMIT %s, %s', (int)$limit[0], (int)$limit[1]);
        } else {
            $sql[] = sprintf('LIMIT %s', (int)$limit[0]);
        }
        return $sql;
    }

    protected function getWhereSql($sql, $where)
    {
        return $this->getConditionSql($sql, $where, 'WHERE');
    }

    protected function getHavingSql($sql, $where)
    {
        return $this->getConditionSql($sql, $where, 'HAVING');
    }

    protected function getConditionSql($sql, $where, $conditionType)
    {
        if (empty($where)) {
            return $sql;
        }
        foreach ($where as $index => $item) {
            if ($item instanceof RawValue) {
                $sql[] = $item->getValue();
                continue;
            }
            list($type, $conditions) = $item;
            if (!$index) {
                $whereType = $conditionType;
            } else {
                $whereType = strtoupper($type);
            }
            list($leftField, $operator, $rightField) = $conditions;
            $rightField = $this->getRightFieldValue($rightField, $operator);
            $operator = strtoupper($operator);
            $sql[] = sprintf('%s %s %s %s', $whereType, $leftField, $operator, $rightField);
        }
        return $sql;
    }

    protected function getRightFieldValue($rightField, $operator)
    {
        if ($rightField instanceof RawValue) {
            return $rightField->getValue();
        }
        // https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html
        if ($operator == 'in' || $operator == 'not in') {
            $rightField = '(' . implode(', ', $this->pdo->quoteArray((array)$rightField)) . ')';
        } else if ($operator === '=' && $rightField === null) {
            $operator = 'IS';
            $rightField = $this->pdo->quoteValue($rightField);
        } else if (($operator === '<>' || $operator === '!=') && $rightField === null) {
            $operator = 'IS NOT';
            $rightField = $this->pdo->quoteValue($rightField);
        } else if ($operator === 'between') {
            $between1 = $this->pdo->quoteValue($rightField[0]);
            $between2 = $this->pdo->quoteValue($rightField[1]);
            $rightField = sprintf('%s AND %s', $between1, $between2);
        } else {
            $rightField = $this->pdo->quoteValue($rightField);
        }
        return $rightField;
    }

    protected function getJoinSql($sql, $join)
    {
        if (empty($join)) {
            return $sql;
        }
        foreach ($join as $item) {
            list($type, $table, $leftField, $operator, $rightField) = $item;
            $joinType = strtoupper($type) . ' JOIN';
            $sql[] = sprintf('%s %s ON %s %s %s', $joinType, $table, $leftField, $operator, $rightField);
        }
        return $sql;
    }

    protected function getGroupBySql($sql, $groupBy)
    {
        if (empty($groupBy)) {
            return $sql;
        }
        $sql[] = 'GROUP BY ' . implode(', ', (array)$groupBy);
        return $sql;
    }

    protected function getOrderBySql($sql, $orderBy)
    {
        if (empty($orderBy)) {
            return $sql;
        }
        $sql[] = 'ORDER BY ' . implode(', ', (array)$orderBy);
        return $sql;
    }
}
<?php

namespace Odan\Database;

use Closure;
use PDOStatement;

class SelectQuery
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

    public function columns($fields): self
    {
        $this->columns = $fields;
        return $this;
    }

    public function from($table, $alias = null): self
    {
        $this->from = [$table, $alias];
        return $this;
    }

    public function join($table, $leftField, $operator, $rightField): self
    {
        $this->join[] = ['inner', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function leftJoin($table, $leftField, $operator, $rightField): self
    {
        $this->join[] = ['left', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function crossJoin($table, $leftField, $operator, $rightField): self
    {
        $this->join[] = ['cross', $table, $leftField, $operator, $rightField];
        return $this;
    }

    public function where(...$conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'AND', $conditions[0]);
            return $this;
        }
        $this->where[] = ['and', $conditions];
        return $this;
    }

    public function orWhere(...$conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'OR', $conditions[0]);
            return $this;
        }
        $this->where[] = ['or', $conditions];
        return $this;
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

    public function orderBy($fields): self
    {
        $this->orderBy = $fields;
        return $this;
    }

    public function groupBy($fields): self
    {
        $this->groupBy = $fields;
        return $this;
    }

    public function having(...$conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'AND', $conditions[0]);
            return $this;
        }
        $this->having[] = ['and', $conditions];
        return $this;
    }

    public function orHaving(...$conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'OR', $conditions[0]);
            return $this;
        }
        $this->having[] = ['or', $conditions];
        return $this;
    }

    public function limit($offset, $rowCount = null): self
    {
        $this->limit = [$offset, $rowCount];
        return $this;
    }

    /**
     * @return PDOStatement
     */
    public function execute(): PDOStatement
    {
        return $this->pdo->query($this->getSql());
    }

    /**
     * @return PDOStatement
     */
    public function getStatement(): PDOStatement
    {
        return $this->pdo->prepare($this->getSql());
    }

    /**
     * @return string SQL string
     */
    public function getSql(): string
    {
        $sql = [];
        $sql[] = 'SELECT' . (($this->distinct) ? ' DISTINCT' : '');
        if (!empty($this->columns)) {
            $sql[] = implode(',', (array)$this->columns);
        }
        if (!empty($this->from)) {
            $sql[] = 'FROM ' . trim(implode(" ", (array)$this->from));
        }
        $sql = $this->getJoinSql($sql, $this->join);
        $sql = $this->getWhereSql($sql, $this->where);
        $sql = $this->getGroupBySql($sql, $this->groupBy);
        $sql = $this->getHavingSql($sql, $this->having);
        $sql = $this->getOrderBySql($sql, $this->orderBy);
        $sql = $this->getLimitSql($sql, $this->limit);
        $result = trim(implode(" ", $sql));
        return $result;
    }

    protected function getLimitSql($sql, $limit): array
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

    protected function getWhereSql($sql, $where): array
    {
        return $this->getConditionSql($sql, $where, 'WHERE');
    }

    protected function getHavingSql($sql, $where): array
    {
        return $this->getConditionSql($sql, $where, 'HAVING');
    }

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
            list($rightField, $operator) = $this->getRightFieldValue($rightField, $operator);

            $sql[] = sprintf('%s %s %s %s', $whereType, $leftField, $operator, $rightField);
        }
        return $sql;
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
        // https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html
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

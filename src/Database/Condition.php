<?php

namespace Odan\Database;

use Closure;

class Condition
{

    /**
     * PDO Connection
     *
     * @var Connection
     */
    protected $pdo;

    /**
     * @var Quoter
     */
    protected $quoter;

    /**
     * PDO Connection
     *
     * @var SelectQuery
     */
    protected $query;

    /**
     * Where clause
     *
     * @var array
     */
    protected $where = [];

    /**
     * Having clause
     *
     * @var array
     */
    protected $having = [];

    /**
     * Constructor.
     *
     * @param Connection $pdo
     * @param QueryInterface $query
     */
    public function __construct(Connection $pdo, QueryInterface $query)
    {
        $this->pdo = $pdo;
        $this->quoter = $pdo->getQuoter();
        $this->query = $query;
    }

    /**
     * Get sql.
     *
     * @param $sql
     * @return array
     */
    public function getWhereSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->where, 'WHERE');
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @return array
     */
    public function getHavingSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->having, 'HAVING');
    }

    /**
     * Where AND condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     * @return self
     */
    public function where($conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'AND', $conditions[0]);
            return $this;
        }
        $this->where[] = ['and', $conditions];
        return $this;
    }

    /**
     * Where OR condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     * @return self
     */
    public function orWhere($conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'OR', $conditions[0]);
            return $this;
        }
        $this->where[] = ['or', $conditions];
        return $this;
    }

    /**
     * Add AND having condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     * @return self
     */
    public function having($conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'AND', $conditions[0]);
            return $this;
        }
        $this->having[] = ['and', $conditions];
        return $this;
    }

    /**
     * Add OR having condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     * @return self
     */
    public function orHaving($conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'OR', $conditions[0]);
            return $this;
        }
        $this->having[] = ['or', $conditions];
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
        $closure($this->query);
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
            $rightField = '(' . implode(', ', $this->quoter->quoteArray((array)$rightField)) . ')';
        } elseif ($comparison == 'greatest' || $comparison == 'interval' || $comparison === 'strcmp') {
            $comparison = '= ' . $comparison;
            $rightField = '(' . implode(', ', $this->quoter->quoteArray((array)$rightField)) . ')';
        } elseif ($comparison === '=' && $rightField === null) {
            $comparison = 'IS';
            $rightField = $this->quoter->quoteValue($rightField);
        } elseif (($comparison === '<>' || $comparison === '!=') && $rightField === null) {
            $comparison = 'IS NOT';
            $rightField = $this->quoter->quoteValue($rightField);
        } elseif ($comparison === 'between' || $comparison === 'not between') {
            $between1 = $this->quoter->quoteValue($rightField[0]);
            $between2 = $this->quoter->quoteValue($rightField[1]);
            $rightField = sprintf('%s AND %s', $between1, $between2);
        } else {
            $rightField = $this->quoter->quoteValue($rightField);
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
    public function getConditionSql(array $sql, $where, $conditionType): array
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
            $leftField = $this->quoter->quoteName($leftField);
            list($rightField, $operator) = $this->getRightFieldValue($rightField, $operator);

            $sql[] = sprintf('%s %s %s %s', $whereType, $leftField, $operator, $rightField);
        }
        return $sql;
    }
}

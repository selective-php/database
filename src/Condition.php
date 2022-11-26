<?php

namespace Selective\Database;

use Closure;

/**
 * Condition.
 */
final class Condition
{
    /**
     * @var Quoter
     */
    private Quoter $quoter;

    /**
     * PDO Connection.
     *
     * @var QueryInterface
     */
    private QueryInterface $query;

    /**
     * Where clause.
     *
     * @var array
     */
    private array $where = [];

    /**
     * Having clause.
     *
     * @var array
     */
    private array $having = [];

    /**
     * Constructor.
     *
     * @param Connection $connection The connection
     * @param QueryInterface $query The query
     */
    public function __construct(Connection $connection, QueryInterface $query)
    {
        $this->quoter = $connection->getQuoter();
        $this->query = $query;
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The sql
     */
    public function getWhereSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->where, 'WHERE');
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     * @param array $where The where
     * @param string $conditionType The condition type
     *
     * @return array The sql
     */
    public function getConditionSql(array $sql, array $where, string $conditionType): array
    {
        if (empty($where)) {
            return $sql;
        }
        foreach ($where as $index => $item) {
            if ($item instanceof RawExp) {
                if ($index === 0) {
                    $sql[] = $conditionType . ' ' . $item->getValue();
                    continue;
                }
                $sql[] = $item->getValue();
                continue;
            }
            [$type, $conditions] = $item;
            if (!$index) {
                $whereType = $conditionType;
            } else {
                $whereType = strtoupper($type);
            }
            if ($conditions[0] instanceof RawExp) {
                $sql[] = $whereType . ' ' . $conditions[0]->getValue();
                continue;
            }
            [$leftField, $operator, $rightField] = $conditions;
            $leftField = $this->quoter->quoteName($leftField);
            [$rightField, $operator] = $this->getRightFieldValue($rightField, $operator);

            $sql[] = sprintf('%s %s %s %s', $whereType, $leftField, $operator, $rightField);
        }

        return $sql;
    }

    /**
     * Comparison Functions and Operators.
     *
     * https://dev.mysql.com/doc/refman/5.7/en/comparison-operators.html
     *
     * @param string|array $rightField The right field
     * @param string|mixed $comparison The comparison
     *
     * @return array The value
     */
    private function getRightFieldValue($rightField, $comparison): array
    {
        if ($comparison === 'in' || $comparison === 'not in') {
            $rightField = '(' . implode(', ', $this->quoter->quoteArray((array)$rightField)) . ')';
        } elseif (
            $comparison === 'greatest' ||
            $comparison === 'least' ||
            $comparison === 'coalesce' ||
            $comparison === 'interval' ||
            $comparison === 'strcmp'
        ) {
            $comparison = '= ' . $comparison;
            $rightField = '(' . implode(', ', $this->quoter->quoteArray((array)$rightField)) . ')';
        } elseif ($comparison === '=' && $rightField === null) {
            $comparison = 'IS';
            $rightField = $this->quoter->quoteValue($rightField);
        } elseif (($comparison === '<>' || $comparison === '!=') && $rightField === null) {
            $comparison = 'IS NOT';
            $rightField = $this->quoter->quoteValue($rightField);
        } elseif ($comparison === 'between' || $comparison === 'not between') {
            /** @var array $rightField */
            $between1 = $this->quoter->quoteValue($rightField[0]);
            $between2 = $this->quoter->quoteValue($rightField[1]);
            $rightField = sprintf('%s AND %s', $between1, $between2);
        } elseif ($rightField instanceof RawExp) {
            $rightField = $rightField->getValue();
        } else {
            $rightField = $this->quoter->quoteValue($rightField);
        }

        // @phpstan-ignore-next-line
        return [$rightField, strtoupper($comparison)];
    }

    /**
     * Get sql.
     *
     * @param array $sql The sql
     *
     * @return array The result
     */
    public function getHavingSql(array $sql): array
    {
        return $this->getConditionSql($sql, $this->having, 'HAVING');
    }

    /**
     * Where AND condition.
     *
     * @param array $conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function where(array $conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('where', 'AND', $conditions[0]);

            return $this;
        }
        $this->where[] = ['and', $conditions];

        return $this;
    }

    /**
     * Adds to a clause through a closure, enclosing within parentheses.
     *
     * @param string $clause The clause to work with, typically 'where' or 'having'
     * @param string $andor Add the condition using this operator, typically 'AND' or 'OR'
     * @param callable $closure The closure that adds to the clause
     *
     * @return void
     */
    private function addClauseCondClosure(string $clause, string $andor, callable $closure): void
    {
        // Retain the prior set of conditions, and temporarily reset the clause
        // for the closure to work with (otherwise there will be an extraneous
        // opening AND/OR keyword)
        /** @var array $set */
        $set = $this->$clause;
        $this->$clause = [];
        // invoke the closure, which will re-populate the $this->$clause
        $closure($this->query);
        // are there new clause elements?
        if (empty($this->$clause)) {
            // no: restore the old ones, and done
            $this->$clause = $set;

            return;
        }

        // Append an opening parenthesis to the prior set of conditions,
        // with AND/OR as needed ...
        if (!empty($set)) {
            $set[] = new RawExp(strtoupper($andor) . ' (');
        } else {
            $set[] = new RawExp('(');
        }

        // Append the new conditions to the set, with indenting
        $sql = [];
        $sql = $this->getConditionSql($sql, $this->$clause, '');
        foreach ($sql as $cond) {
            $set[] = new RawExp($cond);
        }
        $set[] = new RawExp(')');

        // ... then put the full set of conditions back into $this->$clause
        $this->$clause = $set;
    }

    /**
     * Where OR condition.
     *
     * @param array $conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function orWhere(array $conditions): self
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
     * @param array $conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function having(array $conditions): self
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
     * @param array $conditions The conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function orHaving(array $conditions): self
    {
        if ($conditions[0] instanceof Closure) {
            $this->addClauseCondClosure('having', 'OR', $conditions[0]);

            return $this;
        }
        $this->having[] = ['or', $conditions];

        return $this;
    }
}

<?php

namespace Odan\Database;

use Closure;
use PDOStatement;

class SelectQuery extends SelectQueryBuilder
{

    public function distinct()
    {
        $this->distinct = true;
        return $this;
    }

    public function columns($fields): self
    {
        $this->columns = (array)$fields;
        return $this;
    }

    public function from($table): self
    {
        $this->from = $table;
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
    public function query(): PDOStatement
    {
        return $this->pdo->query($this->build());
    }

    /**
     * @return PDOStatement
     */
    public function prepare(): PDOStatement
    {
        return $this->pdo->prepare($this->build());
    }

}

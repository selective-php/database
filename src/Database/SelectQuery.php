<?php

namespace Odan\Database;

use PDOStatement;

/**
 * Class SelectQuery.
 */
final class SelectQuery extends SelectQueryBuilder implements QueryInterface
{
    /**
     * Distinct.
     *
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = 'DISTINCT';

        return $this;
    }

    /**
     * Distinct row.
     *
     * @return self
     */
    public function distinctRow(): self
    {
        $this->distinct = 'DISTINCTROW';

        return $this;
    }

    /**
     * Distinct row.
     *
     * @return self
     */
    public function straightJoin(): self
    {
        $this->straightJoin = 'STRAIGHT_JOIN';

        return $this;
    }

    /**
     * High Priority.
     *
     * @return self
     */
    public function highPriority(): self
    {
        $this->highPriority = 'HIGH_PRIORITY';

        return $this;
    }

    /**
     * Small Result.
     *
     * @return self
     */
    public function smallResult(): self
    {
        $this->resultSize = 'SQL_SMALL_RESULT';

        return $this;
    }

    /**
     * Big Result.
     *
     * @return self
     */
    public function bigResult(): self
    {
        $this->resultSize = 'SQL_BIG_RESULT';

        return $this;
    }

    /**
     * Buffer Result.
     *
     * @return self
     */
    public function bufferResult(): self
    {
        $this->bufferResult = 'SQL_BUFFER_RESULT';

        return $this;
    }

    /**
     * Calc Found Rows.
     *
     * @return self
     */
    public function calcFoundRows(): self
    {
        $this->calcFoundRows = 'SQL_CALC_FOUND_ROWS';

        return $this;
    }

    /**
     * Adds new fields to be returned by a `SELECT` statement when this query is
     * executed. Fields can be passed as an array of strings, array of expression
     * objects, a single expression or a single string.
     *
     * If an array is passed, keys will be used to alias fields using the value as the
     * real field to be aliased. It is possible to alias strings, Expression objects or
     * even other Query objects.
     *
     * This method will append any passed argument to the list of fields to be selected.
     *
     * @param array ...$columns field1, field2, field3, ...
     *
     * @return self
     */
    public function columns(...$columns): self
    {
        if (isset($columns[0]) && is_array($columns[0])) {
            $columns = $columns[0];
        }

        if (empty($this->columns)) {
            $this->columns = $columns;
        } else {
            $this->columns = array_keys(array_replace(array_flip($this->columns), array_flip($columns)));
        }

        return $this;
    }

    /**
     * Alias for sub selects.
     *
     * @param string $alias
     *
     * @return self
     */
    public function alias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * From.
     *
     * @param string $table Table name
     *
     * @return self
     */
    public function from(string $table): self
    {
        $this->from = $table;

        return $this;
    }

    /**
     * UNION is used to combine the result from multiple
     * SELECT statements into a single result set.
     *
     * @param SelectQuery $query the query to combine
     *
     * @return self
     */
    public function union(SelectQuery $query): self
    {
        $this->union[] = ['', $query->build(false)];

        return $this;
    }

    /**
     * UNION ALL is used to combine the result from multiple
     * SELECT statements into a single result set.
     *
     * @param SelectQuery $query the query to combine
     *
     * @return self
     */
    public function unionAll(SelectQuery $query): self
    {
        $this->union[] = ['ALL', $query->build(false)];

        return $this;
    }

    /**
     * UNION DISTINCT is used to combine the result from multiple
     * SELECT statements into a single result set.
     *
     * @param SelectQuery $query the query to combine
     *
     * @return self
     */
    public function unionDistinct(SelectQuery $query): self
    {
        $this->union[] = ['DISTINCT', $query->build(false)];

        return $this;
    }

    /**
     * Join.
     *
     * @param string $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function join(string $table, string $leftField, string $comparison, $rightField): self
    {
        $this->join[] = ['inner', $table, $leftField, $comparison, $rightField];

        return $this;
    }

    /**
     * Inner Join (alias).
     *
     * @param string $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function innerJoin(string $table, string $leftField, string $comparison, $rightField): self
    {
        return $this->join($table, $leftField, $comparison, $rightField);
    }

    /**
     * Left Join.
     *
     * @param string $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function leftJoin(string $table, string $leftField, string $comparison, $rightField): self
    {
        $this->join[] = ['left', $table, $leftField, $comparison, $rightField];

        return $this;
    }

    /**
     * Join with complex conditions.
     *
     * @param string $table Table name
     * @param string $conditions The ON conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function joinRaw(string $table, string $conditions): self
    {
        $this->join[] = ['inner', $table, new RawExp($conditions), null, null, null];

        return $this;
    }

    /**
     * Left join with complex conditions.
     *
     * @param string $table Table name
     * @param string $conditions The ON conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function leftJoinRaw(string $table, string $conditions): self
    {
        $this->join[] = ['left', $table, new RawExp($conditions), null, null, null];

        return $this;
    }

    /**
     * Where AND condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function where(...$conditions): self
    {
        $this->condition->where($conditions);

        return $this;
    }

    /**
     * Add a raw AND WHERE condition.
     *
     * @param string $condition The raw where conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function whereRaw(string $condition): self
    {
        $this->condition->where([new RawExp($condition)]);

        return $this;
    }

    /**
     * Where OR condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function orWhere(...$conditions): self
    {
        $this->condition->orWhere($conditions);

        return $this;
    }

    /**
     * Add a raw OR WHERE condition.
     *
     * @param string $condition The raw where conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function orWhereRaw(string $condition): self
    {
        $this->condition->orWhere([new RawExp($condition)]);

        return $this;
    }

    /**
     * The whereColumn method may be used to verify that two columns are equal.
     *
     * @param string $column Name of the first column
     * @param string $comparison comparison (=,>=,<=,<>,is,is not, ....)
     * @param string $secondColumn Name of the second column
     *
     * @return self
     */
    public function whereColumn(string $column, string $comparison, string $secondColumn): self
    {
        $secondColumn = $this->quoter->quoteName($secondColumn);
        $this->condition->where([$column, $comparison, new RawExp($secondColumn)]);

        return $this;
    }

    /**
     * The whereColumn method may be used to verify that two columns are equal.
     *
     * @param string $column Name of the first column
     * @param string $comparison comparison (=,>=,<=,<>,is,is not, ....)
     * @param string $secondColumn Name of the second column
     *
     * @return self
     */
    public function orWhereColumn(string $column, string $comparison, string $secondColumn): self
    {
        $secondColumn = $this->quoter->quoteName($secondColumn);
        $this->condition->orWhere([$column, $comparison, new RawExp($secondColumn)]);

        return $this;
    }

    /**
     * Order by.
     *
     * @param array ...$fields Column name(s)
     *
     * @return self
     */
    public function orderBy(...$fields): self
    {
        $this->orderBy = $fields;

        return $this;
    }

    /**
     * Group by.
     *
     * @param array ...$fields
     *
     * @return self
     */
    public function groupBy(...$fields): self
    {
        $this->groupBy = $fields;

        return $this;
    }

    /**
     * Add AND having condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function having(...$conditions): self
    {
        $this->condition->having($conditions);

        return $this;
    }

    /**
     * Add OR having condition.
     *
     * @param array ...$conditions (field, comparison, value)
     * or (field, comparison, new RawExp('table.field'))
     * or new RawExp('...')
     *
     * @return self
     */
    public function orHaving(...$conditions): self
    {
        $this->condition->orHaving($conditions);

        return $this;
    }

    /**
     * Add AND having condition.
     *
     * @param string $condition The raw HAVING conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function havingRaw(string $condition): self
    {
        $this->condition->having([new RawExp($condition)]);

        return $this;
    }

    /**
     * Add OR having condition.
     *
     * @param string $condition The raw HAVING conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function orHavingRaw(string $condition): self
    {
        $this->condition->orHaving([new RawExp($condition)]);

        return $this;
    }

    /**
     * Limit the number of rows returned.
     *
     * @param float $rowCount Row count
     *
     * @return self
     */
    public function limit(float $rowCount): self
    {
        $this->limit = $rowCount;

        return $this;
    }

    /**
     * Offset of the first row to return.
     *
     * @param float $offset Offset
     *
     * @return self
     */
    public function offset(float $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * Returns a Raw Expression.
     *
     * @param string $value A raw value. Be careful!
     *
     * @return RawExp Raw Expression
     */
    public function raw(string $value): RawExp
    {
        return new RawExp($value);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @return PDOStatement
     */
    public function execute(): PDOStatement
    {
        return $this->db->query($this->build());
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @return PDOStatement
     */
    public function prepare(): PDOStatement
    {
        return $this->db->prepare($this->build());
    }

    /**
     * SQL functions.
     *
     * @return FunctionBuilder
     */
    public function func()
    {
        return new FunctionBuilder($this->db);
    }
}

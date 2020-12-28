<?php

namespace Selective\Database;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Select Query.
 */
final class SelectQuery implements QueryInterface
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @var Quoter
     */
    private Quoter $quoter;

    /**
     * @var array
     */
    private array $columns = [];

    /**
     * @var string|null
     */
    private ?string $alias = null;

    /**
     * @var string|array
     */
    private $from = '';

    /**
     * @var array
     */
    private array $join = [];

    /**
     * @var array
     */
    private array $union = [];

    /**
     * @var Condition Where conditions
     */
    private Condition $condition;

    /**
     * @var array
     */
    private array $orderBy = [];

    /**
     * @var array
     */
    private array $groupBy = [];

    /**
     * @var int|null
     */
    private ?int $limit = null;

    /**
     * @var int|null
     */
    private ?int $offset = null;

    /**
     * @var string
     */
    private string $distinct = '';

    /**
     * @var string
     */
    private string $calcFoundRows = '';

    /**
     * @var string
     */
    private string $bufferResult = '';

    /**
     * @var string
     */
    private string $resultSize = '';

    /**
     * @var string
     */
    private string $straightJoin = '';

    /**
     * @var string
     */
    private string $highPriority = '';

    /**
     * The constructor.
     *
     * @param Connection $connection The connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->pdo = $connection->getPdo();
        $this->quoter = $connection->getQuoter();
        $this->condition = new Condition($connection, $this);
    }

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
     * @param array $columns The columns as array
     *
     * @return self
     */
    public function columns(array $columns): self
    {
        if (empty($this->columns)) {
            $this->columns = $columns;
        } else {
            foreach ($columns as $column) {
                $this->columns[] = $column;
            }
        }

        return $this;
    }

    /**
     * Alias for sub selects.
     *
     * @param string $alias The alias
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
     * @param string|array $table Table name
     *
     * @return self
     */
    public function from($table): self
    {
        $this->from = $table;

        return $this;
    }

    /**
     * UNION is used to combine the result from multiple
     * SELECT statements into a single result set.
     *
     * @param SelectQuery $query The query to combine
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
     * @param SelectQuery $query The query to combine
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
     * @param SelectQuery $query The query to combine
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
     * @param string|array $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function join($table, string $leftField, string $comparison, $rightField): self
    {
        $this->join[] = ['inner', $table, $leftField, $comparison, $rightField];

        return $this;
    }

    /**
     * Inner Join (alias).
     *
     * @param string|array $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function innerJoin($table, string $leftField, string $comparison, $rightField): self
    {
        return $this->join($table, $leftField, $comparison, $rightField);
    }

    /**
     * Left Join.
     *
     * @param string|array $table Table name
     * @param string $leftField Name of the left field
     * @param string $comparison Comparison (=,<,>,<=,>=,<>,in, not in, between, not between)
     * @param mixed $rightField Value of the right field
     *
     * @return self
     */
    public function leftJoin($table, string $leftField, string $comparison, $rightField): self
    {
        $this->join[] = ['left', $table, $leftField, $comparison, $rightField];

        return $this;
    }

    /**
     * Join with complex conditions.
     *
     * @param string|array $table Table name
     * @param string $conditions The ON conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function joinRaw($table, string $conditions): self
    {
        $this->join[] = ['inner', $table, new RawExp($conditions), null, null, null];

        return $this;
    }

    /**
     * Left join with complex conditions.
     *
     * @param string|array $table Table name
     * @param string $conditions The ON conditions e.g. 'user.id = article.user_id'
     *
     * @return self
     */
    public function leftJoinRaw($table, string $conditions): self
    {
        $this->join[] = ['left', $table, new RawExp($conditions), null, null, null];

        return $this;
    }

    /**
     * Where AND condition.
     *
     * @param array ...$conditions The conditions (field, comparison, value)
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
     * @param array ...$conditions The conditions (field, comparison, value)
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
     * @param string $comparison The comparison type (=,>=,<=,<>,is,is not, ....)
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
     * @param string $comparison The comparison (=,>=,<=,<>,is,is not, ....)
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
     * @param array ...$fields The fields
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
     * @param array ...$conditions The conditions (field, comparison, value)
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
     * @param array ...$conditions The conditions (field, comparison, value)
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
     * @param int $rowCount Row count
     *
     * @return self
     */
    public function limit(int $rowCount): self
    {
        $this->limit = $rowCount;

        return $this;
    }

    /**
     * Offset of the first row to return.
     *
     * @param int $offset Offset
     *
     * @return self
     */
    public function offset(int $offset): self
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
     * @throws RuntimeException
     *
     * @return PDOStatement The pdo statement
     */
    public function execute(): PDOStatement
    {
        $statement = $this->pdo->query($this->build());

        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('Query could not be created');
        }

        return $statement;
    }

    /**
     * Prepares a statement for execution and returns a statement object.
     *
     * @throws RuntimeException
     *
     * @return PDOStatement The pdo statement
     */
    public function prepare(): PDOStatement
    {
        $statement = $this->pdo->prepare($this->build());

        if (!$statement instanceof PDOStatement) {
            throw new RuntimeException('The database statement could not be prepared.');
        }

        return $statement;
    }

    /**
     * SQL functions.
     *
     * @return FunctionBuilder The function builder
     */
    public function func(): FunctionBuilder
    {
        return new FunctionBuilder($this->connection);
    }

    /**
     * Build a SQL string.
     *
     * @param bool $complete The complete
     *
     * @return string SQL string
     */
    public function build(bool $complete = true): string
    {
        $builder = new SelectQueryBuilder($this->connection);

        $sql = [];
        $sql = $builder->getSelectSql(
            $sql,
            [
                $this->distinct,
                $this->highPriority,
                $this->straightJoin,
                $this->resultSize,
                $this->bufferResult,
                $this->calcFoundRows,
            ]
        );
        $sql = $builder->getColumnsSql($sql, $this->columns);
        $sql = $builder->getFromSql($sql, $this->from);
        $sql = $builder->getJoinSql($sql, $this->join);
        $sql = $this->condition->getWhereSql($sql);
        $sql = $builder->getGroupBySql($sql, $this->groupBy);
        $sql = $this->condition->getHavingSql($sql);
        $sql = $builder->getOrderBySql($sql, $this->orderBy);
        $sql = $builder->getLimitSql($sql, $this->limit, $this->offset);
        $sql = $builder->getUnionSql($sql, $this->union);
        $result = trim(implode(' ', $sql));
        $result = $builder->getAliasSql($result, $this->alias);

        if ($complete) {
            $result = trim($result) . ';';
        }

        return $result;
    }
}

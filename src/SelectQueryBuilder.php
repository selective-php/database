<?php

namespace Odan\Database;

use Closure;
use PDO;

/**
 * Select Query.
 */
final class SelectQueryBuilder implements QueryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Quoter
     */
    private $quoter;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->quoter = $connection->getQuoter();
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array $options
     *
     * @return array
     */
    public function getSelectSql(array $sql, array $options = []): array
    {
        $sql[] = trim('SELECT ' . trim(implode(' ', $options)));

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array $inputColumns
     *
     * @return array
     */
    public function getColumnsSql(array $sql, array $inputColumns): array
    {
        if (empty($inputColumns)) {
            $sql[] = '*';

            return $sql;
        }
        $columns = [];
        foreach ($inputColumns as $key => $column) {
            if ($column instanceof Closure) {
                // Sub Select
                $query = new SelectQuery($this->connection);
                $column($query);
                $column = new RawExp($query->build(false));
            }

            if (!is_int($key)) {
                $column = sprintf('%s AS %s', (string)$column, $key);
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
     * @param string $from
     *
     * @return array
     */
    public function getFromSql(array $sql, string $from): array
    {
        if (!empty($from)) {
            $sql[] = 'FROM ' . $this->quoter->quoteName($from);
        }

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array|null $join
     *
     * @return array
     */
    public function getJoinSql(array $sql, array $join = null): array
    {
        if (empty($join)) {
            return $sql;
        }
        foreach ($join as $item) {
            [$type, $table, $leftField, $operator, $rightField] = $item;
            $joinType = strtoupper($type) . ' JOIN';
            $table = $this->quoter->quoteName($table);
            if ($leftField instanceof RawExp) {
                $sql[] = sprintf('%s %s ON (%s)', $joinType, $table, $leftField->getValue());
            } else {
                $leftField = $this->quoter->quoteName($leftField);
                $rightField = $this->quoter->quoteName($rightField);
                $sql[] = sprintf('%s %s ON %s %s %s', $joinType, $table, $leftField, $operator, $rightField);
            }
        }

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array $groupBy
     *
     * @return array
     */
    public function getGroupBySql(array $sql, array $groupBy): array
    {
        if (empty($groupBy)) {
            return $sql;
        }
        $sql[] = 'GROUP BY ' . implode(', ', $this->quoter->quoteByFields($groupBy));

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array $orderBy
     *
     * @return array
     */
    public function getOrderBySql(array $sql, array $orderBy = null): array
    {
        if (empty($orderBy)) {
            return $sql;
        }
        $sql[] = 'ORDER BY ' . implode(', ', $this->quoter->quoteByFields($orderBy));

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param int|null $limit
     * @param int|null $offset
     *
     * @return array
     */
    public function getLimitSql(array $sql, int $limit = null, int $offset = null): array
    {
        if (!isset($limit)) {
            return $sql;
        }
        if ($offset > 0) {
            $sql[] = sprintf('LIMIT %s OFFSET %s', $limit, $offset);
        } else {
            $sql[] = sprintf('LIMIT %s', $limit);
        }

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param array $sql
     * @param array|null $unions
     *
     * @return array
     */
    public function getUnionSql(array $sql, array $unions = null): array
    {
        if (empty($unions)) {
            return $sql;
        }
        foreach ($unions as $union) {
            $sql[] = 'UNION ' . trim($union[0] . ' ' . $union[1]);
        }

        return $sql;
    }

    /**
     * Get sql.
     *
     * @param string $sql
     * @param string|null $alias
     *
     * @return string $sql
     */
    public function getAliasSql(string $sql, string $alias = null): string
    {
        if (!isset($alias)) {
            return $sql;
        }

        return sprintf('(%s) AS %s', $sql, $this->quoter->quoteName($alias));
    }
}

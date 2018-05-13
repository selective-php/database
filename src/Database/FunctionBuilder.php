<?php

namespace Odan\Database;

/**
 * Contains methods related to generating Expression objects
 * with most commonly used SQL functions.
 * This acts as a factory for RawExp objects.
 */
class FunctionBuilder
{
    /**
     * Connection.
     *
     * @var Connection
     */
    protected $db;

    /**
     * Constructor.
     *
     * @param Connection $db
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Calculate a sum. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return RawExp Expression
     */
    public function sum(string $field): RawExp
    {
        $expression = sprintf('SUM(%s)', $this->db->getQuoter()->quoteName($field));

        return new RawExp($expression);
    }

    /**
     * Calculate an average. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return RawExp Expression
     */
    public function avg(string $field): RawExp
    {
        $expression = sprintf('AVG(%s)', $this->db->getQuoter()->quoteName($field));

        return new RawExp($expression);
    }

    /**
     * Calculate the min of a column. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return RawExp Expression
     */
    public function min(string $field): RawExp
    {
        $expression = sprintf('MIN(%s)', $this->db->getQuoter()->quoteName($field));

        return new RawExp($expression);
    }

    /**
     * Calculate the max of a column. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return RawExp Expression
     */
    public function max(string $field): RawExp
    {
        $expression = sprintf('MAX(%s)', $this->db->getQuoter()->quoteName($field));

        return new RawExp($expression);
    }

    /**
     * Calculate the count. The arguments will be treated as literal values.
     *
     * @param string $field Field name (Default is *)
     *
     * @return RawExp Expression
     */
    public function count(string $field = '*'): RawExp
    {
        $expression = sprintf('COUNT(%s)', $this->db->getQuoter()->quoteName($field));

        return new RawExp($expression);
    }

    /**
     * Calculate the count. The arguments will be treated as literal values.
     *
     * @param string $field Field name (Default is *)
     * @param string ...$fields Field names
     *
     * @return RawExp Expression
     */
    public function concat(string $field, string ...$fields): RawExp
    {
        $names = $this->db->getQuoter()->quoteNames(array_merge([$field], $fields));
        $expression = sprintf('CONCAT(%s)', implode(', ', $names));

        return new RawExp($expression);
    }

    /**
     * Returns a Expression representing a call that will return the current date and time (ISO).
     *
     * @return RawExp Expression
     */
    public function now(): RawExp
    {
        return new RawExp('NOW()');
    }
}

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
     * @return FunctionExpression Expression
     */
    public function sum(string $field): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('SUM(%s)', $quoter->quoteName($field));

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Calculate an average. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return FunctionExpression Expression
     */
    public function avg(string $field): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('AVG(%s)', $quoter->quoteName($field));

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Calculate the min of a column. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return FunctionExpression Expression
     */
    public function min(string $field): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('MIN(%s)', $quoter->quoteName($field));

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Calculate the max of a column. The arguments will be treated as literal values.
     *
     * @param string $field Field name
     *
     * @return FunctionExpression Expression
     */
    public function max(string $field): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('MAX(%s)', $quoter->quoteName($field));

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Calculate the count. The arguments will be treated as literal values.
     *
     * @param string $field Field name (Default is *)
     * @param string|null $alias Alias
     *
     * @return FunctionExpression Expression
     */
    public function count(string $field = '*', string $alias = null): RawExp
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('COUNT(%s)', $quoter->quoteName($field));

        if ($alias !== null) {
            $expression .= sprintf(' %s AS %s', $expression, $quoter->quoteName($alias));
        }

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Returns a Expression representing a call that will return the current date and time (ISO).
     *
     * @return FunctionExpression Expression
     */
    public function now(): FunctionExpression
    {
        return new FunctionExpression('NOW()', $this->db->getQuoter());
    }

    /**
     * Returns a Raw Expression.
     *
     * @param string $value A raw value. Be careful!
     * @return RawExp Raw Expression
     */
    public function raw(string $value): RawExp
    {
        return new RawExp($value);
    }
}

<?php

namespace Selective\Database;

/**
 * Contains methods related to generating Expression objects
 * with most commonly used SQL functions.
 * This acts as a factory for RawExp objects.
 */
final class FunctionBuilder
{
    /**
     * Connection.
     *
     * @var Connection
     */
    private Connection $db;

    /**
     * Constructor.
     *
     * @param Connection $db The connection
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
     *
     * @return FunctionExpression Expression
     */
    public function count(string $field = '*'): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = sprintf('COUNT(%s)', $quoter->quoteName($field));

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
     * Create custom SQL function call.
     *
     * @param string $name The name of the function
     * @param mixed ...$parameters The parameters for the function
     *
     * @return FunctionExpression Expression
     */
    public function call(string $name, ...$parameters): FunctionExpression
    {
        $quoter = $this->db->getQuoter();

        $list = implode(', ', $quoter->quoteArray($parameters));
        $expression = sprintf('%s(%s)', strtoupper($name), $list);

        return new FunctionExpression($expression, $quoter);
    }

    /**
     * Returns a quoted field.
     *
     * @param string $field Field name
     *
     * @return FunctionExpression Expression
     */
    public function field(string $field): FunctionExpression
    {
        $quoter = $this->db->getQuoter();
        $expression = $quoter->quoteName($field);

        return new FunctionExpression($expression, $quoter);
    }
}

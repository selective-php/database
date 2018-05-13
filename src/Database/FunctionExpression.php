<?php

namespace Odan\Database;

/**
 * Contains methods related to generating Expression objects
 * with most commonly used SQL functions.
 * This acts as a factory for RawExp objects.
 */
class FunctionExpression extends RawExp
{
    /**
     * @var Quoter
     */
    protected $quoter;

    /**
     * Constructor.
     *
     * @param string $value
     * @param Quoter $quoter
     */
    public function __construct(string $value, Quoter $quoter)
    {
        parent::__construct($value);
        $this->quoter = $quoter;
    }

    /**
     * Alias.
     *
     * @param string|null $alias Alias
     *
     * @return $this
     */
    public function alias(string $alias = null)
    {
        $clone = clone $this;
        $clone->value = sprintf('%s AS %s', $clone->value, $this->quoter->quoteName($alias));

        return $clone;
    }
}

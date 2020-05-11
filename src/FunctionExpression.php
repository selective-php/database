<?php

namespace Selective\Database;

/**
 * Contains methods related to generating Expression objects
 * with most commonly used SQL functions.
 * This acts as a factory for RawExp objects.
 */
final class FunctionExpression extends RawExp
{
    /**
     * @var Quoter
     */
    private $quoter;

    /**
     * Constructor.
     *
     * @param string $value The value
     * @param Quoter $quoter The quoter
     */
    public function __construct(string $value, Quoter $quoter)
    {
        parent::__construct($value);
        $this->quoter = $quoter;
    }

    /**
     * Alias.
     *
     * @param string $alias Alias
     *
     * @return $this The self instance
     */
    public function alias(string $alias): self
    {
        $clone = clone $this;
        $clone->value = sprintf('%s AS %s', $clone->value, $this->quoter->quoteName($alias));

        return $clone;
    }
}

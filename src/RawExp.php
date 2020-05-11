<?php

namespace Selective\Database;

/**
 * Raw Expression.
 */
class RawExp
{
    /**
     * @var string
     */
    protected $value;

    /**
     * Constructor.
     *
     * @param string $value The value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * To string.
     *
     * @return string The string value
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Get value.
     *
     * @return string The string value
     */
    public function getValue(): string
    {
        return $this->value;
    }
}

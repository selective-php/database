<?php

namespace Odan\Database;

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
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * To string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Get value.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}

<?php

namespace Odan\Database;

class RawExp
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    public function getValue()
    {
        return $this->value;
    }
}

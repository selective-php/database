<?php

namespace Odan\Test;

use Odan\Database\RawExp;

/**
 * @coversDefaultClass \Odan\Database\RawExp
 */
class RawExpTest extends BaseTest
{
    /**
     * Test
     *
     * @covers ::getValue
     */
    public function testGetValue()
    {
        $exp = new RawExp('123');
        $this->assertEquals('123', $exp->getValue());
    }

    /**
     * Test
     *
     * @covers ::__toString
     */
    public function testToString()
    {
        $exp = new RawExp('abc');
        $this->assertEquals('abc', $exp->__toString());
    }
}

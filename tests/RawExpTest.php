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

    /**
     * Test
     *
     * @covers ::getValue
     */
    public function testColumnsRaw()
    {
        $select = $this->select()
            ->columns(
                new RawExp('COUNT(*) AS user_count'),
                'status'
            )
            ->from('payments')
            ->where('status', '<>', 1)
            ->groupBy('status')
            ->build();
        $this->assertEquals("SELECT COUNT(*) AS user_count,`status` FROM `payments` WHERE `status` <> '1' GROUP BY `status`;", $select);
    }

    /**
     * Test
     *
     * @covers ::getValue
     */
    public function testColumnsRaw2()
    {
        $select = $this->select()
            ->columns(
                new RawExp('MAX(amount)'),
                new RawExp('MIN(amount)'))
            ->from('payments')
            ->build();
        $this->assertEquals('SELECT MAX(amount),MIN(amount) FROM `payments`;', $select);
    }
}

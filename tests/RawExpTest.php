<?php

namespace Odan\Database\Test;

use Odan\Database\RawExp;

/**
 * @coversDefaultClass \Odan\Database\RawExp
 */
class RawExpTest extends BaseTest
{
    /**
     * Test.
     */
    public function testGetValue()
    {
        $exp = new RawExp('123');
        $this->assertSame('123', $exp->getValue());
    }

    /**
     * Test.
     */
    public function testToString()
    {
        $exp = new RawExp('abc');
        $this->assertSame('abc', $exp->__toString());
    }

    /**
     * Test.
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
        $this->assertSame("SELECT COUNT(*) AS user_count,`status` FROM `payments` WHERE `status` <> '1' GROUP BY `status`;", $select);
    }

    /**
     * Test.
     */
    public function testColumnsRaw2()
    {
        $select = $this->select()
            ->columns(
                new RawExp('MAX(amount)'),
                new RawExp('MIN(amount)')
            )
            ->from('payments')
            ->build();
        $this->assertSame('SELECT MAX(amount),MIN(amount) FROM `payments`;', $select);
    }
}

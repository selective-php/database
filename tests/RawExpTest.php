<?php

namespace Selective\Database\Test;

use Selective\Database\RawExp;

/**
 * @coversDefaultClass \Selective\Database\RawExp
 */
class RawExpTest extends BaseTest
{
    /**
     * Test.
     *
     * @return void
     */
    public function testGetValue(): void
    {
        $exp = new RawExp('123');
        $this->assertSame('123', $exp->getValue());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testToString(): void
    {
        $exp = new RawExp('abc');
        $this->assertSame('abc', $exp->__toString());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testColumnsRaw(): void
    {
        $select = $this->select()
            ->columns([
                new RawExp('COUNT(*) AS user_count'),
                'status',
            ])
            ->from('payments')
            ->where('status', '<>', 1)
            ->groupBy('status')
            ->build();
        $this->assertSame('SELECT COUNT(*) AS user_count,`status` ' .
            "FROM `payments` WHERE `status` <> '1' GROUP BY `status`;", $select);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testColumnsRaw2(): void
    {
        $select = $this->select()
            ->columns([
                new RawExp('MAX(amount)'),
                new RawExp('MIN(amount)'),
            ])
            ->from('payments')
            ->build();
        $this->assertSame('SELECT MAX(amount),MIN(amount) FROM `payments`;', $select);
    }
}

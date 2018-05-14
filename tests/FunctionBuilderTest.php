<?php

declare(strict_types = 1);

namespace Odan\Database\Test;

use Odan\Database\FunctionBuilder;
use Odan\Database\RawExp;

/**
 * @coversDefaultClass \Odan\Database\FunctionBuilder
 */
class FunctionBuilderTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance()
    {
        $func = $this->getConnection()->select()->func();
        $this->assertInstanceOf(FunctionBuilder::class, $func);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testSum()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('SUM(`field`)', $func->sum('field'));
        $this->assertEquals('SUM(`table`.`field`)', $func->sum('table.field'));

        $query = $this->getConnection()->select()->from('payments');
        $query->columns($query->func()->count('amount'));
        $this->assertEquals('SELECT COUNT(`amount`) FROM `payments`;', $query->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAvg()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('AVG(`field`)', $func->avg('field'));
        $this->assertEquals('AVG(`table`.`field`)', $func->avg('table.field'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testMin()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('MIN(`field`)', $func->min('field'));
        $this->assertEquals('MIN(`table`.`field`)', $func->min('table.field'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testMax()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('MAX(`field`)', $func->max('field'));
        $this->assertEquals('MAX(`table`.`field`)', $func->max('table.field'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCount()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('COUNT(*)', $func->count());
        $this->assertEquals('COUNT(`field`)', $func->count('field'));

        $query = $this->getConnection()->select()->from('users');
        $query->columns($query->func()->count());
        $this->assertEquals('SELECT COUNT(*) FROM `users`;', $query->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testNow()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertInstanceOf(RawExp::class, $func->now());
        $this->assertEquals('NOW()', $func->now());
        $this->assertEquals('NOW() AS `alias_field`', $func->now()->alias('alias_field')->getValue());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRaw()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertInstanceOf(RawExp::class, $func->raw(''));
        $this->assertEquals('value', $func->raw('value')->getValue());
    }
}

<?php

declare(strict_types = 1);

namespace Odan\Database\Test;

use Odan\Database\FunctionBuilder;

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
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testConcat()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('CONCAT(`field`)', $func->concat('field'));
        $this->assertEquals('CONCAT(`field`, `field2`, `field3`)', $func->concat('field', 'field2', 'field3'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testNow()
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('NOW()', $func->now());
    }
}

<?php

declare(strict_types = 1);

namespace Selective\Database\Test;

use Selective\Database\FunctionBuilder;
use Selective\Database\FunctionExpression;
use Selective\Database\RawExp;

/**
 * @coversDefaultClass \Selective\Database\FunctionBuilder
 */
class FunctionBuilderTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance(): void
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
        $query->columns([$query->func()->count('amount')]);
        $this->assertEquals('SELECT COUNT(`amount`) FROM `payments`;', $query->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testAvg(): void
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
    public function testMin(): void
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
    public function testMax(): void
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
    public function testCount(): void
    {
        $func = $this->getConnection()->select()->func();

        $this->assertEquals('COUNT(*)', $func->count());
        $this->assertEquals('COUNT(`field`)', $func->count('field'));
        $this->assertEquals('COUNT(`field`) AS `alias_field`', $func->count('field')->alias('alias_field'));

        $query = $this->getConnection()->select()->from('users');
        $query->columns([$query->func()->count()]);
        $this->assertEquals('SELECT COUNT(*) FROM `users`;', $query->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testNow(): void
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
    public function testCustom(): void
    {
        $query = $this->getConnection()->select();
        $func = $query->func();

        // Only values
        $function = $func->call('ifnull', null, 'test')->alias('alias_field');
        $this->assertInstanceOf(FunctionExpression::class, $function);
        $this->assertEquals("IFNULL(NULL, 'test') AS `alias_field`", $function->getValue());

        // only values
        $function = $func->call('repeat', 'a', 1000);
        $this->assertEquals("REPEAT('a', '1000')", $function->getValue());

        // with fields
        $function = $func->call('ifnull', $func->field('users.email'), 'test');
        $this->assertEquals("IFNULL(`users`.`email`, 'test')", $function->getValue());

        // Full query
        $query->columns(
            [
                $func->call(
                    'concat',
                    $func->field('users.first_name'),
                    '-',
                    $func->field('users.last_name')
                ),
            ]
        );
        $query->from('users');

        $this->assertEquals(
            "SELECT CONCAT(`users`.`first_name`, '-', `users`.`last_name`) FROM `users`;",
            $query->build()
        );

        // nested functions
        $query = $this->getConnection()->select();
        $func = $query->func();

        $query->columns([$func->call('length', $func->call('compress', "a'b"))->alias('l')]);
        $this->assertEquals("SELECT LENGTH(COMPRESS('a\'b')) AS `l`;", $query->build());
    }
}

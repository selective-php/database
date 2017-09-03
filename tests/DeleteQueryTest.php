<?php

namespace Odan\Test;

use Odan\Database\DeleteQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\DeleteQuery
 */
class DeleteQueryTest extends BaseTest
{
    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * Test create object.
     *
     * @return void
     * @covers ::__construct
     */
    public function testInstance()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->delete());
    }

    /**
     * @return DeleteQuery
     */
    protected function delete()
    {
        return new DeleteQuery($this->getConnection());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::from
     * @covers ::getDeleteSql
     * @covers ::prepare
     * @covers ::build
     * @covers ::execute
     */
    public function testFrom()
    {
        $delete = $this->delete()->from('test');
        $this->assertEquals("DELETE FROM `test`;", $delete->build());
        $this->assertTrue($delete->execute());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::lowPriority
     * @covers ::getDeleteSql
     * @covers ::prepare
     * @covers ::build
     */
    public function testLowPriority()
    {
        $delete = $this->delete()->lowPriority()->from('test');
        $this->assertInstanceOf(PDOStatement::class, $delete->prepare());
        $this->assertEquals("DELETE LOW_PRIORITY FROM `test`;", $delete->build());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::where
     * @covers ::lowPriority
     * @covers ::getDeleteSql
     * @covers ::ignore
     * @covers ::prepare
     * @covers ::build
     */
    public function testIgnore()
    {
        $delete = $this->delete()->ignore()->from('test')->where('id', '=', '1');
        $this->assertEquals("DELETE IGNORE FROM `test` WHERE `id` = '1';", $delete->build());

        $delete = $this->delete()->lowPriority()->ignore()->from('test')->where('id', '=', '1');
        $this->assertEquals("DELETE LOW_PRIORITY IGNORE FROM `test` WHERE `id` = '1';", $delete->build());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::where
     * @covers ::quick
     * @covers ::getDeleteSql
     * @covers ::ignore
     * @covers ::prepare
     * @covers ::build
     */
    public function testQuick()
    {
        $delete = $this->delete()->quick()->from('test')->where('id', '=', '1');
        $this->assertEquals("DELETE QUICK FROM `test` WHERE `id` = '1';", $delete->build());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::where
     * @covers ::orderBy
     * @covers ::getOrderBySql
     * @covers ::prepare
     * @covers ::build
     */
    public function testOrderBy()
    {
        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy(['id']);
        $this->assertEquals("DELETE FROM `test` WHERE `id` = '1' ORDER BY `id`;", $delete->build());

        $delete = $this->delete()->from('test')->where('id', '=', '1')->orderBy(['id DESC']);
        $this->assertEquals("DELETE FROM `test` WHERE `id` = '1' ORDER BY `id` DESC;", $delete->build());

        // @todo
        //$delete = $this->update()->from('test')->where('id', '=', '1')->orderBy(['test.id ASC']);
        //$this->assertEquals("DELETE FROM `test` WHERE `id` = '1' ORDER BY `test`.`id` ASC;", $delete->build());

        //$delete = $this->update()->from('test')->where('id', '=', '1')->orderBy(['db.test.id ASC']);
        //$this->assertEquals("DELETE FROM `test` WHERE `id` = '1' ORDER BY `db`.`test`.`id` ASC;", $delete->build());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::where
     * @covers ::limit
     * @covers ::getLimitSql
     * @covers ::prepare
     * @covers ::build
     */
    public function testLimit()
    {
        $delete = $this->delete()->from('test')->where('id', '>', '1')->limit(10);
        $this->assertEquals("DELETE FROM `test` WHERE `id` > '1' LIMIT 10;", $delete->build());
    }

    /**
     * Test
     *
     * @covers ::from
     * @covers ::where
     * @covers ::orWhere
     * @covers ::prepare
     * @covers ::build
     * @covers ::getDeleteSql
     * @covers ::getOrderBySql
     * @covers ::getLimitSql
     * @covers ::getDeleteSql
     */
    public function testWhere()
    {
        $delete = $this->delete()->from('test')->where('id', '=', '1')
            ->where('test.id', '=', 1)
            ->orWhere('db.test.id', '>', 2);
        $this->assertEquals("DELETE FROM `test` WHERE `id` = '1' AND `test`.`id` = '1' OR `db`.`test`.`id` > '2';", $delete->build());
    }
}

<?php

namespace Odan\Test;

use Odan\Database\RawExp;
use Odan\Database\UpdateQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\UpdateQuery
 */
class UpdateQueryTest extends BaseTest
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
        $this->assertInstanceOf(UpdateQuery::class, $this->update());
    }

    /**
     * @return UpdateQuery
     */
    protected function update()
    {
        return new UpdateQuery($this->getConnection());
    }

    /**
     * Test
     *
     * @covers ::table
     * @covers ::set
     * @covers ::lowPriority
     * @covers ::getUpdateSql
     * @covers ::prepare
     * @covers ::build
     */
    public function testLowPriority()
    {
        $update = $this->update()->lowPriority()->table('test')->set(['username' => 'admin']);
        $this->assertInstanceOf(PDOStatement::class, $update->prepare());
        $this->assertEquals("UPDATE LOW_PRIORITY `test` SET `username`='admin';", $update->build());
    }

    /**
     * Test
     *
     * @covers ::table
     * @covers ::set
     * @covers ::lowPriority
     * @covers ::getUpdateSql
     * @covers ::ignore
     * @covers ::prepare
     * @covers ::build
     */
    public function testIgnore()
    {
        $update = $this->update()->ignore()->table('test')->set(['username' => 'admin']);
        $this->assertEquals("UPDATE IGNORE `test` SET `username`='admin';", $update->build());

        $update = $this->update()->lowPriority()->ignore()->table('test')->set(['username' => 'admin']);
        $this->assertEquals("UPDATE LOW_PRIORITY IGNORE `test` SET `username`='admin';", $update->build());
    }

    /**
     * Test
     *
     * @covers ::table
     * @covers ::set
     * @covers ::orderBy
     * @covers ::getOrderBySql
     * @covers ::prepare
     * @covers ::build
     */
    public function testOrderBy()
    {
        $update = $this->update()->table('test')->set(['username' => 'admin'])->orderBy(['id']);
        $this->assertEquals("UPDATE `test` SET `username`='admin' ORDER BY `id`;", $update->build());

        $update = $this->update()->table('test')->set(['username' => 'admin'])->orderBy(['id DESC']);
        $this->assertEquals("UPDATE `test` SET `username`='admin' ORDER BY `id` DESC;", $update->build());

        // @todo
        //$update = $this->update()->table('test')->set(['username' => 'admin'])->orderBy(['test.id ASC']);
        //$this->assertEquals("UPDATE `test` SET `username`='admin' ORDER BY `test`.`id` ASC;", $update->build());

        //$update = $this->update()->table('test')->set(['username' => 'admin'])->orderBy(['db.test.id ASC']);
        //$this->assertEquals("UPDATE `test` SET `username`='admin' ORDER BY `db`.`test`.`id` ASC;", $update->build());
    }

    /**
     * Test
     *
     * @covers ::table
     * @covers ::set
     * @covers ::limit
     * @covers ::getLimitSql
     * @covers ::prepare
     * @covers ::build
     */
    public function testLimit()
    {
        $update = $this->update()->table('test')->set(['username' => 'admin'])->limit(10);
        $this->assertEquals("UPDATE `test` SET `username`='admin' LIMIT 10;", $update->build());
    }

    /**
     * Test
     *
     * @covers ::table
     * @covers ::set
     * @covers ::where
     * @covers ::orWhere
     * @covers ::prepare
     * @covers ::build
     * @covers ::getUpdateSql
     * @covers ::getSetSql
     * @covers ::getOrderBySql
     * @covers ::getLimitSql
     * @covers ::getUpdateSql
     */
    public function testWhere()
    {
        $update = $this->update()->table('test')->set(['username' => 'admin'])
        ->where('test.id', '=', 1)
        ->orWhere('db.test.id', '>', 2);
        $this->assertEquals("UPDATE `test` SET `username`='admin' WHERE `test`.`id` = '1' OR `db`.`test`.`id` > '2';", $update->build());
    }
}

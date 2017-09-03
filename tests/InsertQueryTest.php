<?php

namespace Odan\Test;

use Odan\Database\InsertQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\InsertQuery
 */
class InsertQueryTest extends BaseTest
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
        $this->assertInstanceOf(InsertQuery::class, $this->insert());
    }

    /**
     * @return InsertQuery
     */
    protected function insert()
    {
        return new InsertQuery($this->getConnection());
    }

    /**
     * Test
     *
     * @covers ::into
     * @covers ::set
     * @covers ::prepare
     * @covers ::build
     * @covers ::execute
     */
    public function testInto()
    {
        $insert = $this->insert()->into('test')->set(['keyname' => 'admin-007']);
        $this->assertEquals("INSERT INTO `test` SET `keyname`='admin-007';", $insert->build());
        $this->assertInstanceOf(PDOStatement::class, $insert->prepare());
        $this->assertTrue($insert->execute());
    }

    /**
     * Test
     *
     * @covers ::into
     * @covers ::set
     * @covers ::lowPriority
     * @covers ::highPriority
     * @covers ::prepare
     * @covers ::build
     */
    public function testPriority()
    {
        $insert = $this->insert()->lowPriority()->into('test')->set(['username' => 'admin']);
        $this->assertEquals("INSERT LOW_PRIORITY INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->highPriority()->into('test')->set(['username' => 'admin']);
        $this->assertEquals("INSERT HIGH_PRIORITY INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test
     *
     * @covers ::into
     * @covers ::set
     * @covers ::lowPriority
     * @covers ::ignore
     * @covers ::prepare
     * @covers ::build
     */
    public function testIgnore()
    {
        $insert = $this->insert()->ignore()->into('test')->set(['username' => 'admin']);
        $this->assertEquals("INSERT IGNORE INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->lowPriority()->ignore()->into('test')->set(['username' => 'admin']);
        $this->assertEquals("INSERT LOW_PRIORITY IGNORE INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test
     *
     * @covers ::into
     * @covers ::set
     * @covers ::delayed
     * @covers ::ignore
     * @covers ::prepare
     * @covers ::build
     */
    public function testDelayed()
    {
        $insert = $this->insert()->delayed()->into('test')->set(['username' => 'admin']);
        $this->assertEquals("INSERT DELAYED INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test
     *
     * @covers ::into
     * @covers ::set
     * @covers ::onDuplicateKeyUpdate
     * @covers ::prepare
     * @covers ::build
     */
    public function testOnDuplicateKeyUpdate()
    {
        $insert = $this->insert()->ignore()->into('test')->set(['username' => 'admin']);
        $insert->onDuplicateKeyUpdate(['username' => 'admin-01']);
        $this->assertEquals("INSERT IGNORE INTO `test` SET `username`='admin' ON DUPLICATE KEY UPDATE `username`='admin-01';", $insert->build());
    }
}

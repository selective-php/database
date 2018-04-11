<?php

namespace Odan\Test;

use Odan\Database\InsertQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\InsertQuery
 */
class InsertQueryTest extends BaseTest
{
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
     * Test.
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
        $this->assertSame("INSERT INTO `test` SET `keyname`='admin-007';", $insert->build());
        $stmt = $insert->prepare();
        $this->assertInstanceOf(PDOStatement::class, $stmt);
        $this->assertTrue($stmt->execute());
        $this->assertSame(1, $stmt->rowCount());
        $this->assertSame('1', $this->getConnection()->lastInsertId());
    }

    /**
     * Test.
     *
     * @covers ::into
     * @covers ::set
     * @covers ::lastInsertId
     * @covers ::prepare
     * @covers ::build
     * @covers ::execute
     */
    public function testLastInsertId()
    {
        $insert = $this->insert()->into('test')->set(['keyname' => 'admin-007']);
        $insert->execute();
        $this->assertSame('1', $insert->lastInsertId());
    }

    /**
     * Test.
     *
     * @covers ::into
     * @covers ::set
     * @covers ::insertGetId
     * @covers ::prepare
     * @covers ::build
     * @covers ::execute
     */
    public function testInsertGetId()
    {
        $insertGetId = $this->insert()->into('test')->insertGetId(['keyname' => 'admin-007']);
        $this->assertSame('1', $insertGetId);
    }

    /**
     * Test.
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
        $this->assertSame("INSERT LOW_PRIORITY INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->highPriority()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT HIGH_PRIORITY INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
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
        $this->assertSame("INSERT IGNORE INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->lowPriority()->ignore()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT LOW_PRIORITY IGNORE INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
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
        $this->assertSame("INSERT DELAYED INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
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
        $this->assertSame("INSERT IGNORE INTO `test` SET `username`='admin' ON DUPLICATE KEY UPDATE `username`='admin-01';", $insert->build());
    }

    protected function setUp()
    {
        parent::setUp();
        $this->createTestTable();
    }
}

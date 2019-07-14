<?php

namespace Odan\Database\Test;

use Odan\Database\InsertQuery;

/**
 * @coversDefaultClass \Odan\Database\InsertQuery
 */
class InsertQueryTest extends BaseTest
{
    /**
     * Test create object.
     *
     * @return void
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
     */
    public function testInto()
    {
        $insert = $this->insert()->into('test')->set(['keyname' => 'admin-007']);
        $this->assertSame("INSERT INTO `test` SET `keyname`='admin-007';", $insert->build());
        $stmt = $insert->prepare();
        $this->assertTrue($stmt->execute());
        $this->assertSame(1, $stmt->rowCount());
        $this->assertSame('1', $this->getConnection()->getPdo()->lastInsertId());
    }

    /**
     * Test.
     */
    public function testLastInsertId()
    {
        $insert = $this->insert()->into('test')->set(['keyname' => 'admin-007']);
        $insert->execute();
        $this->assertSame('1', $insert->lastInsertId());
    }

    /**
     * Test.
     */
    public function testInsertGetId()
    {
        $insertGetId = $this->insert()->into('test')->insertGetId(['keyname' => 'admin-007']);
        $this->assertSame('1', $insertGetId);
    }

    /**
     * Test.
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
     */
    public function testDelayed()
    {
        $insert = $this->insert()->delayed()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT DELAYED INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
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

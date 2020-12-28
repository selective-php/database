<?php

namespace Selective\Database\Test;

use Selective\Database\InsertQuery;

/**
 * @coversDefaultClass \Selective\Database\InsertQuery
 */
class InsertQueryTest extends BaseTest
{
    /**
     * Set up.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestTable();
    }

    /**
     * Test create object.
     *
     * @return void
     */
    public function testInstance(): void
    {
        $this->assertInstanceOf(InsertQuery::class, $this->insert());
    }

    /**
     * Create insert.
     *
     * @return InsertQuery The query
     */
    protected function insert(): InsertQuery
    {
        return new InsertQuery($this->getConnection());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testInto(): void
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
     *
     * @return void
     */
    public function testLastInsertId(): void
    {
        $insert = $this->insert()->into('test')->set(['keyname' => 'admin-007']);
        $insert->execute();
        $this->assertSame('1', $insert->lastInsertId());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testInsertGetId(): void
    {
        $insertGetId = $this->insert()->into('test')->insertGetId(['keyname' => 'admin-007']);
        $this->assertSame('1', $insertGetId);
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testPriority(): void
    {
        $insert = $this->insert()->lowPriority()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT LOW_PRIORITY INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->highPriority()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT HIGH_PRIORITY INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testIgnore(): void
    {
        $insert = $this->insert()->ignore()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT IGNORE INTO `test` SET `username`='admin';", $insert->build());

        $insert = $this->insert()->lowPriority()->ignore()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT LOW_PRIORITY IGNORE INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testDelayed(): void
    {
        $insert = $this->insert()->delayed()->into('test')->set(['username' => 'admin']);
        $this->assertSame("INSERT DELAYED INTO `test` SET `username`='admin';", $insert->build());
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testOnDuplicateKeyUpdate(): void
    {
        $insert = $this->insert()->ignore()->into('test')->set(['username' => 'admin']);
        $insert->onDuplicateKeyUpdate(['username' => 'admin-01']);
        $this->assertSame(
            "INSERT IGNORE INTO `test` SET `username`='admin' " .
            "ON DUPLICATE KEY UPDATE `username`='admin-01';",
            $insert->build()
        );
    }
}

<?php

namespace Odan\Test;

use Odan\Database\DeleteQuery;
use Odan\Database\InsertQuery;
use Odan\Database\UpdateQuery;
use Odan\Database\Repository;
use PDO;

/**
 * @coversDefaultClass \Odan\Database\Repository
 */
class RepositoryTest extends BaseTest
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
     */
    public function testInstance()
    {
        $table = $this->getRepository();
        $this->assertInstanceOf(Repository::class, $table);
    }

    /**
     * Test
     *
     * @covers ::select
     */
    public function testSelect()
    {
        //$db = $this->getConnection();
        $newRow = array(
            'keyname' => 'test',
            'keyvalue' => '123'
        );
        $table = $this->getRepository();
        $table->insert($newRow)->execute();

        $select = $this->getRepository()->select()->columns('id', 'keyname', 'keyvalue')->from('test');
        $row = $select->query()->fetch(PDO::FETCH_ASSOC);

        $expected = array(
            'id' => '1',
            'keyname' => 'test',
            'keyvalue' => '123'
        );
        $this->assertEquals($expected, $row);
    }

    /**
     * Test
     *
     * @covers ::insert
     */
    public function testInsert()
    {
        $table = $this->getRepository();
        $this->assertTrue($table->insert(['id' => 1, 'keyname' => 'value'])->execute());
    }

    /**
     * Test
     *
     * @covers ::update
     */
    public function testUpdate()
    {
        $table = $this->getRepository();
        $this->assertTrue($table->update(['id' => 1], ['keyname' => 'value'])->execute());
    }

    /**
     * Test
     *
     * @covers ::delete
     */
    public function testDelete()
    {
        $table = $this->getRepository();
        $this->assertTrue($table->delete()->execute());
        $this->assertTrue($table->delete(['id' => 1])->execute());
    }
}

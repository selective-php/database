<?php

namespace Odan\Test;

use Odan\Database\DeleteQuery;
use Odan\Database\InsertQuery;
use Odan\Database\UpdateQuery;
use Odan\Database\Table;

/**
 * @coversDefaultClass \Odan\Database\Table
 */
class TableTest extends BaseTest
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
        $table = $this->getTable();
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test
     *
     * @covers ::select
     * @covers ::getQuery
     * @covers \Odan\Database\SelectQuery::__construct
     * @covers \Odan\Database\SelectQuery::execute
     * @covers \Odan\Database\InsertQuery::execute
     */
    public function testNewSelect()
    {
        //$db = $this->getConnection();
        $newRow = array(
            'keyname' => 'test',
            'keyvalue' => '123'
        );
        $this->getQuery()->insert()->into('test')->values($newRow)->execute();

        $select = $this->getTable()->select()->columns(['id', 'keyname', 'keyvalue'])->from('test');
        $row = $select->execute()->fetch();

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
     * @covers ::getQuery
     * @covers  \Odan\Database\InsertQuery::__construct
     */
    public function testNewInsert()
    {
        $this->assertInstanceOf(InsertQuery::class, $this->getTable()->insert());
    }

    /**
     * Test
     *
     * @covers ::update
     * @covers ::getQuery
     * @covers  \Odan\Database\UpdateQuery::__construct
     */
    public function testNewUpdate()
    {
        $this->assertInstanceOf(UpdateQuery::class, $this->getTable()->update());
    }

    /**
     * Test
     *
     * @covers ::delete
     * @covers ::getQuery
     * @covers  \Odan\Database\DeleteQuery::__construct
     */
    public function testNewDelete()
    {
        $this->assertInstanceOf(DeleteQuery::class, $this->getTable()->delete());
    }
}

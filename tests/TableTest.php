<?php

namespace Odan\Test;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\UpdateInterface;
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
     * @covers ::newSelect
     * @covers ::getQuery
     */
    public function testNewSelect()
    {
        $db = $this->getConnection();
        $newRow = array(
            'keyname' => 'test',
            'keyvalue' => '123'
        );
        $insert = $this->getQuery()->newInsert()->into('test')->cols($newRow);
        $db->executeQuery($insert);

        $select = $this->getTable()->newSelect()->cols(['id', 'keyname', 'keyvalue'])->from('test');
        $row =$db->executeQuery($select)->fetch();

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
     * @covers ::newInsert
     * @covers ::getQuery
     */
    public function testNewInsert()
    {
        $this->assertInstanceOf(InsertInterface::class, $this->getTable()->newInsert());
    }

    /**
     * Test
     *
     * @covers ::newUpdate
     * @covers ::getQuery
     */
    public function testNewUpdate()
    {
        $this->assertInstanceOf(UpdateInterface::class, $this->getTable()->newUpdate());
    }

    /**
     * Test
     *
     * @covers ::newDelete
     * @covers ::getQuery
     */
    public function testNewDelete()
    {
        $this->assertInstanceOf(DeleteInterface::class, $this->getTable()->newDelete());
    }
}

<?php

namespace Odan\Test;

use Odan\Database\DeleteQuery;
use Odan\Database\InsertQuery;
use Odan\Database\QueryFactory;
use Odan\Database\SelectQuery;
use Odan\Database\UpdateQuery;
use PDOStatement;

/**
 * @coversDefaultClass \Odan\Database\QueryFactory
 */
class QueryFactoryTest extends BaseTest
{
    /**
     * Test.
     *
     * @return void
     * @covers ::__construct
     * @covers ::select
     */
    public function testSelect()
    {
        $factory = new QueryFactory($this->getConnection());
        $this->assertInstanceOf(SelectQuery::class, $factory->select());
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::__construct
     * @covers ::update
     */
    public function testUpdate()
    {
        $factory = new QueryFactory($this->getConnection());
        $this->assertInstanceOf(UpdateQuery::class, $factory->update());
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::__construct
     * @covers ::insert
     */
    public function testInsert()
    {
        $factory = new QueryFactory($this->getConnection());
        $this->assertInstanceOf(InsertQuery::class, $factory->insert());
    }

    /**
     * Test.
     *
     * @return void
     * @covers ::__construct
     * @covers ::delete
     */
    public function testDelete()
    {
        $factory = new QueryFactory($this->getConnection());
        $this->assertInstanceOf(DeleteQuery::class, $factory->delete());
    }
}

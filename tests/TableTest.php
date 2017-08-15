<?php

namespace Odan\Test;

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
}

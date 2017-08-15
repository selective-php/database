<?php

namespace Odan\Test;

use Odan\Database\Schema;

/**
 * @coversDefaultClass \Odan\Database\Schema
 */
class SchemaTest extends BaseTest
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
        $schema = $this->getSchema();
        $this->assertInstanceOf(Schema::class, $schema);
    }

    /**
     * Test setDbName method
     *
     * @return void
     * @covers ::setDatabase
     * @covers ::getDatabase
     * @covers ::existDatabase
     * @covers ::getDatabase
     * @covers ::getDatabases
     */
    public function testSetDbName()
    {
        $schema = $this->getSchema();
        $dbName = $schema->getDatabase();
        if ($schema->existDatabase('test1')) {
            $schema->setDatabase('test1');
            $this->assertEquals('test1', $schema->getDatabase());
        }

        $schema->setDatabase($dbName);
        $this->assertEquals($dbName, $schema->getDatabase());

        $databases = $schema->getDatabases();
        $this->assertEquals(true, in_array('information_schema', $databases));
        $this->assertEquals(true, in_array('database_test', $databases));

        $databases = $schema->getDatabases('information_sch%');
        $this->assertEquals(true, in_array('information_schema', $databases));
        $this->assertEquals(1, count($databases));
    }

    /**
     * Test getTables method
     *
     * @return void
     * @covers ::getTables
     * @covers ::existTable
     * @covers ::dropTable
     * @covers ::clearTable
     * @covers ::renameTable
     * @covers ::parseTableName
     * @covers ::truncateTable
     * @covers ::copyTable
     * @covers ::getColumns
     * @covers ::getTableSchemaId
     * @covers ::compareTableSchema
     * @covers \Odan\Database\Connection::queryValue
     * @covers \Odan\Database\Connection::queryValues
     * @covers \Odan\Database\Connection::insertRows
     * @covers \Odan\Database\Connection::insertRow
     * @covers \Odan\Database\Connection::updateRow
     * @covers \Odan\Database\Connection::deleteRow
     * @covers \Odan\Database\Connection::queryMapColumn
     * @covers \Odan\Database\Connection::executeQuery
     * @covers \Odan\Database\Connection::newUpdate
     * @covers \Odan\Database\Connection::newSelect
     * @covers \Odan\Database\Connection::newDelete
     * @covers \Odan\Database\Connection::newInsert
     */
    public function testTables()
    {
        $db = $this->getConnection();
        $schema = $this->getSchema();
        $table = $this->getTable();

        if ($schema->existTable('test')) {
            $result = $schema->dropTable('test');
            $this->assertEquals(0, $result);
        }

        $result = $schema->existTable('test');
        $this->assertEquals(false, $result);

        $result = $schema->existTable('database_test.test_not_existing');
        $this->assertEquals(false, $result);

        $result = $schema->existTable('notexistingdb.noexistingtable');
        $this->assertEquals(false, $result);

        $result = $this->createTestTable();
        $this->assertEquals(0, $result);

        $result = $schema->existTable('database_test.test');
        $this->assertEquals(true, $result);

        $result = $schema->existTable('notexistingdb.noexistingtable');
        $this->assertEquals(false, $result);

        $result = $schema->getTableSchemaId('test');
        $this->assertEquals('567e34247e52e1ebec081130b34020384b0b7bbd', $result);

        $result = $schema->getTableSchemaId('database_test.test');
        $this->assertEquals('567e34247e52e1ebec081130b34020384b0b7bbd', $result);

        $result = $schema->compareTableSchema('test', 'test');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('database_test.test', 'test');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('information_schema.tables', 'information_schema.tables');
        $this->assertSame(true, $result);

        $result = $schema->compareTableSchema('information_schema.tables', 'information_schema.views');
        $this->assertSame(false, $result);

        $tables = $schema->getTables();
        $this->assertEquals(array(0 => 'test'), $tables);

        $tables = $schema->getTables('te%');
        $this->assertEquals(array(0 => 'test'), $tables);

        $columns = $schema->getColumns('test');
        $this->assertSame(true, !empty($columns));
        $this->assertSame(10, count($columns));
        $this->assertSame('id', $columns[0]['column_name']);
        $this->assertSame('keyname', $columns[1]['column_name']);
        $this->assertSame('keyvalue', $columns[2]['column_name']);
        $this->assertSame('boolvalue', $columns[3]['column_name']);

        $columns = $schema->getColumns('database_test.test');
        $this->assertSame(true, !empty($columns));
        $this->assertSame(10, count($columns));

        $insert = $this->getQuery()->newInsert()->into('test')->cols(array(
            'keyname' => 'test',
            'keyvalue' => '123'
        ));
        $stmt = $db->executeQuery($insert);
        $this->assertTrue($stmt->rowCount() > 0);

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row
        // is 1 if the row is inserted as a new row, and 2 if an existing row is updated.
        // http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
        $insert =  $this->getQuery()->newInsert()
            ->into('test')
            ->cols(array(
                'id' => 1,
                'keyname' => 'test',
                'keyvalue' => '123',
                'boolvalue' => 1))
            ->onDuplicateKeyUpdateCols(array(
                'id' => 1,
                'keyname' => 'testx',
                'keyvalue' => '123',
                'boolvalue' => 1));
        $stmt = $db->executeQuery($insert);
        $result = $stmt->rowCount();
        $this->assertSame(2, $result);

        $result = $db->lastInsertId();
        $this->assertEquals(1, $result);

        $result = $db->query("SELECT COUNT(*) AS count FROM `test`")->fetchAll();
        $this->assertEquals(array(0 => array('count' => 1)), $result);

        $result = $db->queryValue("SELECT COUNT(*) AS count FROM `test`", 'count');
        $this->assertSame('1', $result);

        $result = $db->queryValue("SELECT * FROM `test` WHERE id = 9999999;", 'id');
        $this->assertSame(null, $result);

        $rows = array();
        $result = $table->insertRows('test', $rows);
        $this->assertSame(0, $result);

        $rows = array(
            0 => array('keyname' => 'test', 'keyvalue' => '123'),
            1 => array('keyname' => 'test2', 'keyvalue' => '1234')
        );
        $result = $table->insertRows('test', $rows);
        $this->assertSame(2, $result);

        $result = $db->lastInsertId();
        $this->assertSame('3', $result);

        $result = $db->queryValue("SELECT COUNT(*) AS count FROM `test`", 'count');
        $this->assertSame('3', $result);

        $result = $schema->truncateTable('test');
        $this->assertSame(0, $result);

        $result = $db->queryValue("SELECT COUNT(*) AS count FROM `test`", 'count');
        $this->assertSame('0', $result);

        $result = $table->insertRows('test', $rows);
        $this->assertSame(2, $result);

        $result = $db->queryValues("SELECT id,keyvalue FROM `test`", 'keyvalue');
        $this->assertEquals(array('123', '1234'), $result);

        $result = $db->queryMapColumn("SELECT id,keyname,keyvalue FROM `test`", 'keyname');
        $expected = array(
            'test' =>
                array(
                    'id' => '1',
                    'keyname' => 'test',
                    'keyvalue' => '123',
                ),
            'test2' =>
                array(
                    'id' => '2',
                    'keyname' => 'test2',
                    'keyvalue' => '1234',
                ),
        );
        $this->assertEquals($expected, $result);

        $result = $schema->clearTable('test');
        $this->assertSame(2, $result);

        $result = $db->queryValue("SELECT COUNT(*) AS count FROM `test`", 'count');
        $this->assertSame('0', $result);

        $result = $db->queryValue("SHOW TABLE STATUS FROM `database_test` LIKE 'test'; ", 'Auto_increment');
        $this->assertSame('3', $result);

        $rows = array();
        for ($i = 0; $i < 1000; $i++) {
            $rows[] = array('keyname' => 'test', 'keyvalue' => 'value-' . $i);
        }
        $result = $table->insertRows('test', $rows);
        $this->assertSame(1000, $result);

        $result = $db->query("SELECT keyname,keyvalue FROM test;")->fetchAll();
        $this->assertSame(true, $rows == $result);

        $fields = array(
            'keyname' => 'test-new',
            'keyvalue' => 'value-new'
        );
        //$update = $db->newUpdate()->table('test')->cols($fields)->where('keyname = ?', ['test']);
        //$update = $db->newUpdate()->table('test')->cols($fields)->where('keyname = ?', 'test');
        //$stmt = $db->executeQuery($update);
        $stmt = $table->updateRow('test', $fields, ['keyname' => 'test']);
        $this->assertSame(1000, $stmt->rowCount());

        $stmt = $table->deleteRow('test', array('id' => '10'));
        $this->assertSame(1, $stmt->rowCount());

        $stmt = $table->deleteRow('test', array('id' => '9999999'));
        $stmt->execute();
        $this->assertSame(0, $stmt->rowCount());

        $result = $schema->renameTable('test', 'temp');
        $this->assertSame(0, $result);

        $result = $schema->renameTable('temp', 'test');
        $this->assertSame(0, $result);

        $result = $schema->copyTable('test', 'test_copy');
        $this->assertSame(0, $result);

        $result = $schema->existTable('test_copy');
        $this->assertSame(true, $result);

        $schema->dropTable('test_copy');

        // With data
        $result = $schema->copyTable('test', 'test_copy');
        $this->assertSame(0, $result);

        $result = $schema->existTable('test_copy');
        $this->assertSame(true, $result);

        $schema->dropTable('test_copy');
    }

    /**
     * Test getTables method
     *
     * @return void
     * @covers ::getColumnNames
     */
    public function testGetColumnNames()
    {
        $schema = $this->getSchema();
        $result = $schema->getColumnNames('test');
        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['keyname']));
    }
}

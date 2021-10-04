<?php

namespace Selective\Database\Test;

use PDO;
use Selective\Database\Schema;

/**
 * @coversDefaultClass \Selective\Database\Schema
 */
class SchemaTest extends BaseTest
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
        $schema = $this->getSchema();
        $this->assertInstanceOf(Schema::class, $schema);
    }

    /**
     * Test setDbName method.
     *
     * @return void
     */
    public function testSetDbName(): void
    {
        $schema = $this->getSchema();
        $dbName = $schema->getDatabase();
        if ($schema->existDatabase('test1')) {
            $schema->setDatabase('test1');
            $this->assertSame('test1', $schema->getDatabase());
        }

        $schema->setDatabase($dbName);
        $this->assertSame($dbName, $schema->getDatabase());

        $databases = $schema->getDatabases();
        $this->assertContains('information_schema', $databases);
        $this->assertContains('database_test', $databases);

        $databases = $schema->getDatabases('information_sch%');
        $this->assertContains('information_schema', $databases);
        $this->assertCount(1, $databases);
    }

    /**
     * Test getTables method.
     *
     * @return void
     */
    public function testTables(): void
    {
        $db = $this->getConnection();
        $schema = $this->getSchema();

        if ($schema->existTable('test')) {
            $schema->dropTable('test');
        }

        $result = $schema->existTable('test');
        $this->assertFalse($result);

        $result = $schema->existTable('database_test.test_not_existing');
        $this->assertFalse($result);

        $result = $schema->existTable('notexistingdb.noexistingtable');
        $this->assertFalse($result);

        $this->createTestTable();
        $this->assertTrue($schema->existTable('test'));
        $this->assertTrue($schema->existTable('database_test.test'));
        $this->assertFalse($schema->existTable('notexistingdb.noexistingtable'));

        $tables = $schema->getTables();
        $this->assertSame([0 => 'test'], $tables);

        $tables = $schema->getTables('te%');
        $this->assertSame([0 => 'test'], $tables);

        $columns = $schema->getColumns('test');
        $this->assertNotEmpty($columns);
        $this->assertCount(10, $columns);
        $this->assertSame('id', $columns[0]['COLUMN_NAME']);
        $this->assertSame('keyname', $columns[1]['COLUMN_NAME']);
        $this->assertSame('keyvalue', $columns[2]['COLUMN_NAME']);
        $this->assertSame('boolvalue', $columns[3]['COLUMN_NAME']);

        $columns = $schema->getColumns('database_test.test');
        $this->assertNotEmpty($columns);
        $this->assertCount(10, $columns);

        $insert = $this->getConnection()->insert()->into('test')->set(
            [
                'keyname' => 'test',
                'keyvalue' => '123',
            ]
        );
        $stmt = $insert->prepare();
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() > 0);

        // With ON DUPLICATE KEY UPDATE, the affected-rows value per row
        // is 1 if the row is inserted as a new row, and 2 if an existing row is updated.
        // http://dev.mysql.com/doc/refman/5.0/en/insert-on-duplicate.html
        $insert = $db->insert()->into('test')->set(
            [
                'id' => 1,
                'keyname' => 'test',
                'keyvalue' => '123',
                'boolvalue' => 1,
            ]
        )->onDuplicateKeyUpdate(
            [
                'id' => 1,
                'keyname' => 'testx',
                'keyvalue' => '123',
                'boolvalue' => 1,
            ]
        );
        $stmt = $insert->prepare();
        $stmt->execute();
        $result = $stmt->rowCount();
        $this->assertSame(2, $result);

        $result = $db->getPdo()->lastInsertId();
        $this->assertSame('1', $result);

        $query = $db->select()->from('test');
        $result = $query->columns([$query->func()->count()->alias('count')])->execute()->fetchAll();
        $this->assertSame([0 => ['count' => '1']], $result);

        $query = $db->select()->from('test');
        $result = $query->columns([$query->func()->count()->alias('count')])->execute()->fetchColumn();
        $this->assertSame('1', $result);

        $query = $db->select()->from('test');
        $result = $query->columns(['id'])->where('id', '=', 9999999)->execute()->fetchColumn() ?: null;
        $this->assertNull($result);

        $rows = [
            0 => ['keyname' => 'test', 'keyvalue' => '123'],
            1 => ['keyname' => 'test2', 'keyvalue' => '1234'],
        ];
        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(2, $result->rowCount());

        $result = $db->getPdo()->lastInsertId();
        $this->assertSame('2', $result);

        $query = $db->select()->from('test');
        $result = $query->columns([$query->func()->count()->alias('count')])->execute()->fetchColumn();
        $this->assertSame('3', $result);

        $schema->truncateTable('test');

        $query = $db->select()->from('test');
        $result = $query->columns([$query->func()->count()->alias('count')])->execute()->fetchColumn();
        $this->assertSame('0', $result);

        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(2, $result->rowCount());

        $result = $db->select()->from('test')->columns(['id', 'keyvalue'])->execute()->fetchAll();
        $this->assertSame(
            [
                0 => [
                    'id' => '1',
                    'keyvalue' => '123',
                ],
                1 => [
                    'id' => '2',
                    'keyvalue' => '1234',
                ],
            ],
            $result
        );

        $db->delete()->from('test')->execute();

        $query = $db->select()->from('test');
        $result = $query->columns([$query->func()->count()->alias('count')])->execute()->fetchColumn();
        $this->assertSame('0', $result);

        $statement = $db->getPdo()->query("SHOW TABLE STATUS FROM `database_test` LIKE 'test';");
        $result = $statement ? $statement->fetch() : null;
        $this->assertSame('3', $result['Auto_increment']);

        $rows = [];
        for ($i = 0; $i < 100; $i++) {
            $rows[] = ['keyname' => 'test', 'keyvalue' => 'value-' . $i];
        }
        $result = $db->insert()->into('test')->set($rows)->prepare();
        $result->execute();
        $this->assertSame(100, $result->rowCount());

        $statement = $db->getPdo()->query('SELECT keyname,keyvalue FROM test;');
        $result = $statement ? $statement->fetchAll(PDO::FETCH_ASSOC) : null;
        $this->assertEquals($rows, $result);

        $fields = [
            'keyname' => 'test-new',
            'keyvalue' => 'value-new',
        ];
        $stmt = $db->update()->table('test')->set($fields)->where('keyname', '=', 'test')->prepare();
        $stmt->execute();
        $this->assertSame(100, $stmt->rowCount());

        $stmt = $db->delete()->from('test')->where('id', '=', '10')->prepare();
        $stmt->execute();
        $this->assertSame(1, $stmt->rowCount());

        $stmt = $db->delete()->from('test')->where('id', '=', '9999999')->prepare();
        $stmt->execute();
        $this->assertSame(0, $stmt->rowCount());

        $schema->renameTable('test', 'temp');
        $this->assertTrue($schema->existTable('temp'));

        $schema->renameTable('temp', 'test');
        $this->assertTrue($schema->existTable('test'));

        $schema->copyTable('test', 'test_copy');
        $this->assertTrue($schema->existTable('test_copy'));

        $schema->dropTable('test_copy');

        // With data
        $schema->copyTable('test', 'test_copy');
        $this->assertTrue($schema->existTable('test_copy'));

        $schema->dropTable('test_copy');
    }

    /**
     * Test getTables method.
     *
     * @return void
     */
    public function testGetColumnNames(): void
    {
        $schema = $this->getSchema();
        $result = $schema->getColumnNames('test');
        $this->assertTrue(isset($result['id']));
        $this->assertTrue(isset($result['keyname']));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testRenameTable(): void
    {
        $schema = $this->getSchema();
        $schema->renameTable('test', 'test_copy');
        $this->assertTrue($schema->existTable('test_copy'));
        $this->assertFalse($schema->existTable('test'));
    }

    /**
     * Test.
     *
     * @return void
     */
    public function testCopyTable(): void
    {
        $schema = $this->getSchema();
        $schema->copyTable('test', 'test_copy');
        $this->assertTrue($schema->existTable('test_copy'));
        $this->assertTrue($schema->existTable('test'));
    }
}

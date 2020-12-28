<?php

namespace Selective\Database\Test;

use PDO;
use PHPUnit\Framework\TestCase;
use Selective\Database\Connection;
use Selective\Database\Schema;
use Selective\Database\SelectQuery;

/**
 * Test.
 */
abstract class BaseTest extends TestCase
{
    /**
     * @var Connection|null
     */
    protected ?Connection $connection = null;

    /**
     * @var ?Schema
     */
    protected ?Schema $schema = null;

    /**
     * Create test table.
     *
     * @return void
     */
    protected function createTestTable(): void
    {
        $db = $this->getConnection();
        $schema = $this->getSchema();

        if (!$schema->existDatabase('database_test')) {
            $schema->createDatabase('database_test');
        }

        $schema->useDatabase('database_test');

        foreach ($schema->getTables() as $table) {
            $schema->dropTable($table);
        }

        $db->getPdo()->exec(
            'CREATE TABLE `test` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `keyname` VARCHAR(255) COLLATE utf8_unicode_ci,
            `keyvalue` VARCHAR(255) COLLATE utf8_unicode_ci,
            `boolvalue` TINYINT(1) NOT NULL DEFAULT 0,
            `created` DATETIME DEFAULT NULL,
            `created_user_id` INT(11) DEFAULT NULL,
            `updated` DATETIME DEFAULT NULL,
            `updated_user_id` INT(11) DEFAULT NULL,
            `deleted` DATETIME DEFAULT NULL,
            `deleted_user_id` INT(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `created_user_id` (`created_user_id`),
            KEY `updated_user_id` (`updated_user_id`),
            KEY `deleted_user_id` (`deleted_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;'
        );
    }

    /**
     * Get PDO.
     *
     * @return PDO The connection
     */
    protected function getPdo(): PDO
    {
        $host = '127.0.0.1';
        $username = 'root';
        $password = isset($_SERVER['GITHUB_ACTIONS']) ? 'root' : '';
        $charset = 'utf8';
        $collate = 'utf8_unicode_ci';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate",
        ];

        return new PDO("mysql:host=$host;charset=$charset", $username, $password, $options);
    }

    /**
     * Get connection.
     *
     * @return Connection The connection
     */
    protected function getConnection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = new Connection($this->getPdo());
        }

        return $this->connection;
    }

    /**
     * @return Schema
     */
    protected function getSchema(): Schema
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this->getConnection());
        }

        return $this->schema;
    }

    /**
     * @return SelectQuery
     */
    protected function select(): SelectQuery
    {
        return new SelectQuery($this->getConnection());
    }
}

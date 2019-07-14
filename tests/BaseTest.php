<?php

namespace Odan\Database\Test;

use Odan\Database\Connection;
use Odan\Database\Schema;
use Odan\Database\SelectQuery;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * ConnectionTest.
 */
abstract class BaseTest extends TestCase
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * Create test table.
     *
     * @return int
     */
    protected function createTestTable()
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

        $result = $db->getPdo()->exec('CREATE TABLE `test` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;');

        return $result;
    }

    /**
     * @return PDO
     */
    protected function getPdo(): PDO
    {
        $host = '127.0.0.1';
        $username = 'root';
        $password = '';
        $charset = 'utf8';
        $collate = 'utf8_unicode_ci';

        return new PDO("mysql:host=$host;charset=$charset", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate",
        ]);
    }

    /**
     * @return Connection
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
    protected function getSchema()
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this->getConnection());
        }

        return $this->schema;
    }

    /**
     * @return SelectQuery
     */
    protected function select()
    {
        return new SelectQuery($this->getConnection());
    }
}

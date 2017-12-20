<?php

namespace Odan\Test;

use Odan\Database\Connection;
use Odan\Database\Schema;
use Odan\Database\SelectQuery;
use PHPUnit\Framework\TestCase;
use PDO;

/**
 * ConnectionTest
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
     * @return Connection
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $host = '127.0.0.1';
            $dbname = 'database_test';
            $username = 'root';
            $password = '';
            $charset = 'utf8';
            $collate = 'utf8_unicode_ci';
            $this->connection = new Connection("mysql:host=$host;dbname=$dbname;charset=$charset", $username, $password, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate"
            ));
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
     * Create test table
     *
     * @return int
     */
    protected function createTestTable()
    {
        $db = $this->getConnection();
        $schema = $this->getSchema();
        foreach ($schema->getTables() as $table) {
            $schema->dropTable($table);
        }

        $result = $db->exec("CREATE TABLE `test` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `keyname` varchar(255) COLLATE utf8_unicode_ci,
            `keyvalue` varchar(255) COLLATE utf8_unicode_ci,
            `boolvalue` tinyint(1) NOT NULL DEFAULT 0,
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        return $result;
    }

    /**
     * @return SelectQuery
     */
    protected function select()
    {
        return new SelectQuery($this->getConnection());
    }
}

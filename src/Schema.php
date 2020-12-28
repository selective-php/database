<?php

namespace Selective\Database;

use JsonException;
use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Schema.
 */
final class Schema
{
    /**
     * @var PDO
     */
    private PDO $pdo;

    /**
     * @var Quoter
     */
    private Quoter $quoter;

    /**
     * The constructor.
     *
     * @param Connection $connection The connection
     */
    public function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
        $this->quoter = $connection->getQuoter();
    }

    /**
     * Switch database.
     *
     * @param string $dbName The database name
     *
     * @return void
     *
     * @deprecated Use useDatabase instead
     */
    public function setDatabase(string $dbName): void
    {
        $this->pdo->exec('USE ' . $this->quoter->quoteName($dbName) . ';');
    }

    /**
     * Return current database name.
     *
     * @return string The database name
     */
    public function getDatabase(): string
    {
        return (string)$this->createStatement('SELECT database() AS dbname;')->fetchColumn();
    }

    /**
     * Check if a database exists.
     *
     * @param string $dbName The database name
     *
     * @return bool Status
     */
    public function existDatabase(string $dbName): bool
    {
        $sql = 'SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = %s;';

        $sql = sprintf($sql, $this->quoter->quoteValue($dbName));
        $row = $this->createStatement($sql)->fetch(PDO::FETCH_ASSOC);

        return !empty($row['SCHEMA_NAME']);
    }

    /**
     * Returns all databases.
     *
     * @param string|null $like The optional like expression e.g. 'information%schema';
     *
     * @return array The database names
     */
    public function getDatabases(string $like = null): array
    {
        $sql = 'SHOW DATABASES;';
        if ($like !== null) {
            $sql = sprintf('SHOW DATABASES WHERE `database` LIKE %s;', $this->quoter->quoteValue($like));
        }

        $statement = $this->createStatement($sql);

        $result = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row['Database'];
        }

        return $result;
    }

    /**
     * Create a database.
     *
     * @param string $dbName The database name
     * @param string $characterSet The character set
     * @param string $collate The collation
     *
     * @return void
     */
    public function createDatabase(
        string $dbName,
        string $characterSet = 'utf8mb4',
        string $collate = 'utf8mb4_unicode_ci'
    ): void {
        $sql = 'CREATE DATABASE %s CHARACTER SET %s COLLATE %s;';
        $sql = vsprintf(
            $sql,
            [
                $this->quoter->quoteName($dbName),
                $this->quoter->quoteValue($characterSet),
                $this->quoter->quoteValue($collate),
            ]
        );

        $this->pdo->exec($sql);
    }

    /**
     * Change the database.
     *
     * @param string $dbName The database name
     *
     * @return bool Success
     */
    public function useDatabase(string $dbName): bool
    {
        $sql = sprintf('USE %s;', $this->quoter->quoteName($dbName));

        return (bool)$this->pdo->exec($sql);
    }

    /**
     * Return all Tables from Database.
     *
     * @param string $like A like expression, (optional) e.g. 'information%'
     *
     * @return array The tables
     */
    public function getTables($like = null): array
    {
        if ($like === null) {
            $sql = 'SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = database()';
        } else {
            $sql = sprintf(
                'SELECT TABLE_NAME
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = database()
                AND TABLE_NAME LIKE %s;',
                $this->quoter->quoteValue($like)
            );
        }

        $statement = $this->createStatement($sql);

        $result = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row['TABLE_NAME'];
        }

        return $result;
    }

    /**
     * Check if table exist.
     *
     * @param string $tableName The table name
     *
     * @return bool Status
     */
    public function existTable(string $tableName): bool
    {
        [$dbName, $tableName] = $this->parseTableName($tableName);

        $sql = 'SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = %s
            AND TABLE_NAME = %s;';

        $sql = sprintf($sql, $dbName, $tableName);
        $row = $this->createStatement($sql)->fetch(PDO::FETCH_ASSOC);

        return isset($row['TABLE_NAME']);
    }

    /**
     * Split table into database name and table name.
     *
     * @param string $tableName The table name
     *
     * @return array The database name and table name
     */
    private function parseTableName(string $tableName): array
    {
        $dbName = 'database()';
        if (strpos($tableName, '.') !== false) {
            $parts = explode('.', $tableName);
            $dbName = $this->quoter->quoteValue($parts[0]);
            $tableName = $this->quoter->quoteValue($parts[1]);
        } else {
            $tableName = $this->quoter->quoteValue($tableName);
        }

        return [$dbName, $tableName];
    }

    /**
     * Delete a table.
     *
     * @param string $tableName The table name
     *
     * @return void
     */
    public function dropTable(string $tableName): void
    {
        $this->pdo->exec(sprintf('DROP TABLE IF EXISTS %s;', $this->quoter->quoteName($tableName)));
    }

    /**
     * Truncate (drop and re-create) a table
     * Any AUTO_INCREMENT value is reset to its start value.
     *
     * @param string $tableName The table name
     *
     * @return void
     */
    public function truncateTable(string $tableName): void
    {
        $this->pdo->exec(sprintf('TRUNCATE TABLE %s;', $this->quoter->quoteName($tableName)));
    }

    /**
     * Rename table.
     *
     * @param string $from Old table name
     * @param string $to New table name
     *
     * @return void
     */
    public function renameTable(string $from, string $to): void
    {
        $from = $this->quoter->quoteName($from);
        $to = $this->quoter->quoteName($to);
        $this->pdo->exec(sprintf('RENAME TABLE %s TO %s;', $from, $to));
    }

    /**
     * Copy an existing table to a new table.
     *
     * @param string $tableNameSource Source table name
     * @param string $tableNameDestination New table name
     *
     * @return void
     */
    public function copyTable(string $tableNameSource, string $tableNameDestination): void
    {
        $tableNameSource = $this->quoter->quoteName($tableNameSource);
        $tableNameDestination = $this->quoter->quoteName($tableNameDestination);
        $this->pdo->exec(sprintf('CREATE TABLE %s LIKE %s;', $tableNameDestination, $tableNameSource));
    }

    /**
     * Returns the column names of a table as an array.
     *
     * @param string $tableName The table name
     *
     * @return array The column names
     */
    public function getColumnNames(string $tableName): array
    {
        $result = [];
        foreach ($this->getColumns($tableName) as $value) {
            $field = $value['COLUMN_NAME'];
            $result[$field] = $field;
        }

        return $result;
    }

    /**
     * Returns all columns in a table.
     *
     * @param string $tableName The table name
     *
     * @return array The column name
     */
    public function getColumns(string $tableName): array
    {
        $sql = 'SELECT
            COLUMN_NAME,
            ORDINAL_POSITION,
            COLUMN_DEFAULT,
            IS_NULLABLE,
            DATA_TYPE,
            CHARACTER_MAXIMUM_LENGTH,
            CHARACTER_OCTET_LENGTH,
            NUMERIC_PRECISION,
            NUMERIC_SCALE,
            CHARACTER_SET_NAME,
            COLLATION_NAME,
            COLUMN_TYPE,
            COLUMN_KEY,
            EXTRA,
            PRIVILEGES,
            COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = %s
            AND TABLE_NAME = %s;
            ORDER BY ORDINAL_POSITION';

        [$dbName, $tableName] = $this->parseTableName($tableName);
        $sql = sprintf($sql, $dbName, $tableName);

        return (array)$this->createStatement($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compare two tables and returns true if the table schema match.
     *
     * @param string $tableName1 The table name 1
     * @param string $tableName2 The table name 2
     *
     * @return bool Status
     */
    public function compareTableSchema(string $tableName1, string $tableName2): bool
    {
        $schema1 = $this->getTableSchemaId($tableName1);
        $schema2 = $this->getTableSchemaId($tableName2);

        return $schema1 === $schema2;
    }

    /**
     * Calculate a hash key (SHA1) using a table schema
     * Used to quickly compare table structures or schema versions.
     *
     * @param string $tableName The table name
     *
     * @throws JsonException
     *
     * @return string The table schema hash
     *
     * @deprecated
     */
    public function getTableSchemaId(string $tableName): string
    {
        $sql = sprintf('SHOW FULL COLUMNS FROM %s;', $this->quoter->quoteName($tableName));
        $rows = $this->createStatement($sql)->fetchAll(PDO::FETCH_ASSOC);

        return sha1((string)json_encode($rows, JSON_THROW_ON_ERROR));
    }

    /**
     * Create pdo statement.
     *
     * @param string $sql The sql
     *
     * @throws RuntimeException
     *
     * @return PDOStatement The statement
     */
    private function createStatement(string $sql): PDOStatement
    {
        $statement = $this->pdo->query($sql);

        if (!$statement) {
            throw new RuntimeException('Query could not be created');
        }

        return $statement;
    }
}

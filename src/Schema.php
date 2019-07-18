<?php

namespace Odan\Database;

use PDO;

/**
 * Schema.
 */
final class Schema
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Quoter
     */
    private $quoter;

    /**
     * Constructor.
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
     * @return bool Success
     */
    public function setDatabase(string $dbName): bool
    {
        $this->pdo->exec('USE ' . $this->quoter->quoteName($dbName) . ';');

        return true;
    }

    /**
     * Return current database name.
     *
     * @return string The database name
     */
    public function getDatabase(): string
    {
        return $this->pdo->query('SELECT database() AS dbname;')->fetchColumn();
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
        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        return !empty($row['SCHEMA_NAME']);
    }

    /**
     * Returns all databases.
     *
     * @param string|null $like (optional) e.g. 'information%schema';
     *
     * @return array The database names
     */
    public function getDatabases(string $like = null): array
    {
        $sql = 'SHOW DATABASES;';
        if ($like !== null) {
            $sql = sprintf('SHOW DATABASES WHERE `database` LIKE %s;', $this->quoter->quoteValue($like));
        }

        $statement = $this->pdo->query($sql);

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
     * @return bool Success
     */
    public function createDatabase(
        string $dbName,
        string $characterSet = 'utf8mb4',
        string $collate = 'utf8mb4_unicode_ci'
    ): bool {
        $sql = 'CREATE DATABASE %s CHARACTER SET %s COLLATE %s;';
        $sql = vsprintf($sql, [
            $this->quoter->quoteName($dbName),
            $this->quoter->quoteValue($characterSet),
            $this->quoter->quoteValue($collate),
        ]);

        $this->pdo->exec($sql);

        return true;
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
        $result = $this->pdo->exec($sql);

        return (bool)$result;
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
            $sql = 'SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = database()';
        } else {
            $sql = sprintf('SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = database()
                AND table_name LIKE %s;', $this->quoter->quoteValue($like));
        }

        $statement = $this->pdo->query($sql);

        $result = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row['table_name'];
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

        $sql = 'SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = %s
            AND table_name = %s;';

        $sql = sprintf($sql, $dbName, $tableName);
        $row = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

        return isset($row['table_name']);
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
     * @return bool Success
     */
    public function dropTable(string $tableName): bool
    {
        $this->pdo->exec(sprintf('DROP TABLE IF EXISTS %s;', $this->quoter->quoteName($tableName)));

        return true;
    }

    /**
     * Truncate (drop and re-create) a table
     * Any AUTO_INCREMENT value is reset to its start value.
     *
     * @param string $tableName The table name
     *
     * @return bool Success
     */
    public function truncateTable(string $tableName): bool
    {
        $this->pdo->exec(sprintf('TRUNCATE TABLE %s;', $this->quoter->quoteName($tableName)));

        return true;
    }

    /**
     * Rename table.
     *
     * @param string $from Old table name
     * @param string $to New table name
     *
     * @return bool Success
     */
    public function renameTable(string $from, string $to): bool
    {
        $from = $this->quoter->quoteName($from);
        $to = $this->quoter->quoteName($to);
        $this->pdo->exec(sprintf('RENAME TABLE %s TO %s;', $from, $to));

        return true;
    }

    /**
     * Copy an existing table to a new table.
     *
     * @param string $tableNameSource Source table name
     * @param string $tableNameDestination New table name
     *
     * @return bool Success
     */
    public function copyTable(string $tableNameSource, string $tableNameDestination): bool
    {
        $tableNameSource = $this->quoter->quoteName($tableNameSource);
        $tableNameDestination = $this->quoter->quoteName($tableNameDestination);
        $this->pdo->exec(sprintf('CREATE TABLE %s LIKE %s;', $tableNameDestination, $tableNameSource));

        return true;
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
            $field = $value['column_name'];
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
            column_name,
            column_default,
            is_nullable,
            data_type,
            character_maximum_length,
            character_octet_length,
            numeric_precision,
            numeric_scale,
            character_set_name,
            collation_name,
            column_type,
            column_key,
            extra,
            `privileges`,
            column_comment
            FROM information_schema.columns
            WHERE table_schema = %s
            AND table_name = %s;';

        [$dbName, $tableName] = $this->parseTableName($tableName);
        $sql = sprintf($sql, $dbName, $tableName);

        $result = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return $result ?: [];
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
     * @return string The table schema hash
     */
    public function getTableSchemaId(string $tableName): string
    {
        $sql = sprintf('SHOW FULL COLUMNS FROM %s;', $this->quoter->quoteName($tableName));
        $rows = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        return sha1(json_encode($rows));
    }
}

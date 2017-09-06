<?php

namespace Odan\Database;

use PDO;

/**
 * Class Schema
 */
class Schema
{

    /** @var Connection */
    protected $db = null;

    /**
     * @var Quoter
     */
    protected $quoter;

    public function __construct(Connection $db)
    {
        $this->db = $db;
        $this->quoter = $db->getQuoter();
    }

    /**
     * Switch database
     *
     * @param string $dbName
     * @return bool
     */
    public function setDatabase($dbName)
    {
        return $this->db->exec('USE ' . $this->quoter->quoteName($dbName) . ';');
    }

    /**
     * Return current database name
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->db->query('SELECT database() AS dbname;')->fetchColumn(0);
    }

    /**
     * Check if a table exists
     *
     * @param string $dbName
     * @return bool
     */
    public function existDatabase($dbName)
    {
        $sql = "SELECT SCHEMA_NAME
            FROM INFORMATION_SCHEMA.SCHEMATA
            WHERE SCHEMA_NAME = %s;";

        $sql = sprintf($sql, $this->quoter->quoteValue($dbName));
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return !empty($row['SCHEMA_NAME']);
    }

    /**
     * Returns all databases
     *
     * @param string $like (optional) e.g. 'information%schema';
     * @return array
     */
    public function getDatabases($like = null)
    {
        $sql = 'SHOW DATABASES;';
        if ($like !== null) {
            $sql = sprintf('SHOW DATABASES WHERE `database` LIKE %s;', $this->quoter->quoteValue($like));
        }
        return $this->db->queryValues($sql, 'Database');
    }

    /**
     * Return all Tables from Database
     *
     * @param string $like (optional) e.g. 'information%'
     * @return array
     */
    public function getTables($like = null)
    {
        if ($like === null) {
            $sql = "SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = database()";
        } else {
            $sql = sprintf("SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = database()
                AND table_name LIKE %s;", $this->quoter->quoteValue($like));
        };
        return $this->db->queryValues($sql, 'table_name');
    }

    /**
     * Split table into dbname and table name
     *
     * @param string $tableName table
     * @return array
     */
    protected function parseTableName($tableName)
    {
        $dbName = 'database()';
        if (strpos($tableName, '.') !== false) {
            $parts = explode('.', $tableName);
            $dbName = $this->quoter->quoteValue($parts[0]);
            $tableName = $this->quoter->quoteValue($parts[1]);
        } else {
            $tableName = $this->quoter->quoteValue($tableName);
        }
        return array($dbName, $tableName);
    }

    /**
     * Check if table exist
     *
     * @param string $tableName
     * @return bool
     */
    public function existTable($tableName)
    {
        list($dbName, $tableName) = $this->parseTableName($tableName);

        $sql = "SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = %s
            AND table_name = %s;";

        $sql = sprintf($sql, $dbName, $tableName);
        $row = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        return (isset($row['table_name']));
    }

    /**
     * Delete a table
     *
     * @param string $tableName
     * @return int affected
     */
    public function dropTable($tableName)
    {
        return $this->db->exec(sprintf('DROP TABLE IF EXISTS %s;', $this->quoter->quoteName($tableName)));
    }

    /**
     * Clear table content. Delete all rows.
     *
     * @param string $tableName
     * @return bool
     */
    public function clearTable($tableName)
    {
        return $this->db->exec(sprintf('DELETE FROM %s;', $this->quoter->quoteName($tableName)));
    }

    /**
     * Truncate (drop and re-create) a table
     * Any AUTO_INCREMENT value is reset to its start value.
     *
     * @param string $tableName
     * @return int
     */
    public function truncateTable($tableName)
    {
        return $this->db->exec(sprintf('TRUNCATE TABLE %s;', $this->quoter->quoteName($tableName)));
    }

    /**
     * Rename table
     *
     * @param string $tableSource
     * @param string $tableTarget
     * @return bool Status
     */
    public function renameTable($tableSource, $tableTarget)
    {
        $tableSource = $this->quoter->quoteName($tableSource);
        $tableTarget = $this->quoter->quoteName($tableTarget);
        $this->db->exec(sprintf('RENAME TABLE %s TO %s;', $tableSource, $tableTarget));
        return true;
    }

    /**
     * Copy an existing table to a new table
     *
     * @param string $tableNameSource source table name
     * @param string $tableNameDestination new table name
     * @return bool Status
     */
    public function copyTable($tableNameSource, $tableNameDestination)
    {
        $tableNameSource = $this->quoter->quoteName($tableNameSource);
        $tableNameDestination = $this->quoter->quoteName($tableNameDestination);
        $this->db->exec(sprintf('CREATE TABLE %s LIKE %s;', $tableNameDestination, $tableNameSource));
        return true;
    }

    /**
     * Returns all columns in a table
     *
     * @param string $tableName
     * @return array
     */
    public function getColumns($tableName)
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

        list($dbName, $tableName) = $this->parseTableName($tableName);
        $sql = sprintf($sql, $dbName, $tableName);
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the column names of a table as an array
     *
     * @param string $tableName
     * @return array
     */
    public function getColumnNames($tableName)
    {
        $result = array();
        foreach ($this->getColumns($tableName) as $value) {
            $field = $value['column_name'];
            $result[$field] = $field;
        }
        return $result;
    }

    /**
     * Calculate a hash key (SHA1) using a table schema
     * Used to quickly compare table structures or schema versions
     *
     * @param string $tableName
     * @return string
     */
    public function getTableSchemaId($tableName)
    {
        $sql = sprintf('SHOW FULL COLUMNS FROM %s;', $this->quoter->quoteName($tableName));
        $rows = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return sha1(json_encode($rows));
    }

    /**
     * Compare two tables and returns true if the table schema match
     *
     * @param string $tableName1
     * @param string $tableName2
     * @return bool
     */
    public function compareTableSchema($tableName1, $tableName2)
    {
        $schema1 = $this->getTableSchemaId($tableName1);
        $schema2 = $this->getTableSchemaId($tableName2);
        return $schema1 === $schema2;
    }
}

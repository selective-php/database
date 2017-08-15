<?php

namespace Odan\Database;

class Schema
{

    /** @var Connection */
    protected $db = null;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Switch database
     *
     * @param string $dbName
     * @return bool
     */
    public function setDatabase($dbName)
    {
        return $this->db->exec('USE ' . $this->db->ident($dbName) . ';');
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

        $sql = sprintf($sql, $this->db->esc($dbName));
        $row = $this->db->query($sql)->fetch();
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
            $sql = sprintf('SHOW DATABASES WHERE `database` LIKE %s;', $this->db->esc($like));
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
                AND table_name LIKE %s;", $this->db->esc($like));
        };
        return $this->db->queryValues($sql, 'table_name');
    }

    /**
     * Split table into dbname and tablename
     *
     * @param string $tableName table
     * @return array
     */
    protected function parseTableName($tableName)
    {
        $dbName = 'database()';
        if (strpos($tableName, '.') !== false) {
            $parts = explode('.', $tableName);
            $dbName = $this->db->esc($parts[0]);
            $tableName = $this->db->esc($parts[1]);
        } else {
            $tableName = $this->db->esc($tableName);
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
        $row = $this->db->query($sql)->fetch();
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
        return $this->db->exec(sprintf('DROP TABLE IF EXISTS %s;', $this->db->ident($tableName)));
    }

    /**
     * Clear table content. Delete all rows.
     *
     * @param string $tableName
     * @return bool
     */
    public function clearTable($tableName)
    {
        return $this->db->exec(sprintf('DELETE FROM %s;', $this->db->ident($tableName)));
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
        return $this->db->exec(sprintf('TRUNCATE TABLE %s;', $this->db->ident($tableName)));
    }

    /**
     * Rename table
     *
     * @param string $tableSource
     * @param string $tableTarget
     * @return int
     */
    public function renameTable($tableSource, $tableTarget)
    {
        $tableSource = $this->db->ident($tableSource);
        $tableTarget = $this->db->ident($tableTarget);
        return $this->db->exec(sprintf('RENAME TABLE %s TO %s;', $tableSource, $tableTarget));
    }

    /**
     * Copy an existing table to a new table
     *
     * @param string $tableNameSource source table name
     * @param string $tableNameDestination new table name
     * @return bool
     */
    public function copyTable($tableNameSource, $tableNameDestination)
    {
        $tableNameSource = $this->db->ident($tableNameSource);
        $tableNameDestination = $this->db->ident($tableNameDestination);
        return $this->db->exec(sprintf('CREATE TABLE %s LIKE %s;', $tableNameDestination, $tableNameSource));
        ;
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
        return $this->db->query($sql)->fetchAll();
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
     * Calculate a hashkey (SHA1) using a table schema
     * Used to quickly compare table structures or schema versions
     *
     * @param string $tableName
     * @return string
     */
    public function getTableSchemaId($tableName)
    {
        $sql = sprintf('SHOW FULL COLUMNS FROM %s;', $this->db->ident($tableName));
        $rows = $this->db->query($sql)->fetchAll();
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

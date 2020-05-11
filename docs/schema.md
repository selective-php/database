## Schema

This class is a utility to modify database and table schemas.

```php
use Selective\Database\Connection;

$connection = new Connection($dsn, $username, $password, $options);
$schema = new \Odan\Database\Schema($connection);
```

### Get Current database

```php
$databaseName = $schema->getDatabase();
```

### Change database

```php
$schema->setDatabase('my_database');
```

### Check if a database exists

```php
$exits = $schema->existDatabase('my_database');
```

### Find all databases

```php
$databases = $schema->getDatabases();
```

Find all databases by name:

```php
$databases = $schema->getDatabases('information%schema');
```

### Create a database

```php
$success = $schema->createDatabase('my_database');
```

Create a table with custom character set and collation:

```php
$success = $schema->createDatabase('my_database', 'utf8mb4', 'utf8mb4_unicode_ci');
```

### Change the database

```php
$success = $schema->useDatabase('another_database_name');
```

### Find all tables

```php
$tables = $schema->getTables();
```

Find all tables by name:

```php
$tables = $schema->getTables('information%');
```

### Check whether table exists

```php
$tableExists = $schema->existTable('test');
```

### Truncate a table

Delete all rows and reset the auto increment value:

```php
$success = $schema->truncateTable('test');
```

### Rename a table

```php
$success = $schema->renameTable('from', 'to');
```

### Copy a table

Copy an existing table to a new table:

```php
$success = $schema->copyTable('from', 'to');
```

### Get all column names of a table

```php
$columns = $schema->getColumnNames('my_table');
```

### Get all column details of a table

```php
$columns = $schema->getColumns('my_table');
```

### Compare tables

Compare two tables and returns true if the table schema match:

```php
$isMatch = $schema->compareTableSchema('my_table1', 'my_table2');
```

### Get the table schema hash

Calculate a hash key (SHA1) using a table schema.
Used to quickly compare table structures or schema versions.

```php
$hash = $schema->getTableSchemaId('my_table1', 'my_table2');
```

**Next page:** [Index](readme.md)

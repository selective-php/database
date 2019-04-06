# Documentation

* [Connection](#connection)
* [Select](selects.md)
* [Insert](inserts.md)
* [Update](updates.md)
* [Delete](deletes.md)
* [Schema](schema.md)
* [Compression](compression.md)

## Introduction

The database query builder provides a convenient, fluent interface for creating and executing database queries. 
It can be used to perform most database operations in your application and works on all supported database systems (MySql).

The query builder uses quoting to protect your application against SQL injection attacks.

## Connection

Create a new database Connection:

```php
<?php

use Odan\Database\Connection;

$host = '127.0.0.1';
$database = 'test';
$username = 'root';
$password = '';
$charset = 'utf8';
$collate = 'utf8_unicode_ci';
$dsn = "mysql:host=$host;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate"
];

$connection = new Connection($dsn, $username, $password, $options);
```

**Next page:** [Select](selects.md)

# Database
 
A fluent SQL query builder.

[![Latest Version on Packagist](https://img.shields.io/github/release/odan/database.svg?style=flat-square)](https://packagist.org/packages/odan/database)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/odan/database/master.svg?style=flat-square)](https://travis-ci.org/odan/database)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/odan/database.svg?style=flat-square)](https://scrutinizer-ci.com/g/odan/database/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/quality/g/odan/database.svg?style=flat-square)](https://scrutinizer-ci.com/g/odan/database/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/database.svg?style=flat-square)](https://packagist.org/packages/odan/database/stats)

## Features

* Fluent SQL query builder
* Table schema information and manipulation

## Installation

```shell
composer require odan/database
```

## Requirements

* PHP >= 7.1
* MySQL, MariaDB

## Usage

```php
$dsn = "mysql:host=127.0.0.1;dbname=test;charset=utf8";
$username = 'root';
$password = '';

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_PERSISTENT => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8 COLLATE utf8_unicode_ci"
];

$pdo = new \PDO($dsn, $username, $password, $options);

$connection = new \Odan\Database\Connection($pdo);

$query = $connection->select()->from('users');

$query->columns('id', 'username', 'email');

$rows = $query->execute()->fetchAll() ?: [];
    
foreach ($rows as $row) {
    var_dump($row);
}
```

## Documentation

The database query builder provides a convenient, fluent interface for creating and executing database queries. It can be used to perform most database operations in your PHP website and application.

For more details how to build queries please read the **[documentation](https://odan.github.io/database/)**.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


## Similar libraries

* [cakephp/database](https://github.com/cakephp/database)
* [illuminate/database](https://github.com/illuminate/database)
* [zendframework/zend-db](https://github.com/zendframework/zend-db)
* [spiral/database](https://github.com/spiral/database)

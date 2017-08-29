# Database
 
A fluent SQL query builder.

[![Latest Version on Packagist](https://img.shields.io/github/release/odan/database.svg)](https://github.com/odan/database/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/odan/database.svg?branch=master)](https://travis-ci.org/odan/database)
[![Coverage Status](https://scrutinizer-ci.com/g/odan/database/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/database/code-structure)
[![Quality Score](https://scrutinizer-ci.com/g/odan/database/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/odan/database/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/database.svg)](https://packagist.org/packages/odan/database)


## Features

* Extended PDO connection
* SQL query builder (select, insert, update, delete)
* Table schema informations and manipulation
* Data compression

## Installation

```shell
composer require odan/database
```

## Requirements

* PHP 7.0+
* MySQL

## Query Builder

The database query builder provides a convenient, fluent interface to creating and running database queries. It can be used to perform most database operations in your application, and works on all supported database systems.

For more details how to build queries read the **[documentation](docs/index.md)**.

## Example

Create a new database Connection:

```php
<?php

use Odan\Database\Connection;
use Odan\Database\QueryFactory;
use Odan\Database\RawValue
use PDO;

$host = '127.0.0.1';
$database = 'test';
$username = 'root';
$password = '';
$charset = 'utf8';
$collate = 'utf8_unicode_ci';

$pdo = new Connection("mysql:host=$host;dbname=$database;charset=$charset", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_PERSISTENT => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES $charset COLLATE $collate"
    ]
);

// Create a query factory object
$query = new QueryFactory($pdo);
```

Build a select statement:

```php
$select = $query->select()
    ->distinct()
    ->columns(['id', 'username'])
    ->from('users u')
    ->join('customers c', 'c.created_by', '=', 'u.id')
    ->leftJoin('articles a', 'a.created_by', '=', 'u.id')
    ->where('u.id', '>=', 1)
    ->where('u.deleted', '=', 0)
    ->orWhere('u.username', 'like', "%a'a%")
    ->orWhere('u.username', 'not like', "%a'b%")
    ->orWhere('u.id', 'in', [1, 2, 3])
    ->orWhere('u.id', 'not in', [4, 5, null])
    ->orWhere('u.id', '=', null)
    ->orWhere('u.id', '!=', null)
    ->orWhere(function(SelectQuery $query) {
        $query->where('1', '<>', '2');
        $query->where('2', '=', null);
        $query->where('3', '>', '5');
        $query->orWhere(function(SelectQuery $query) {
            $query->where('a.id', '=', new RawValue('b.id'));
            $query->orWhere('c.id', '=', new RawValue('u.id'));
        });
    })
    ->where('u.id', '>=', 0)
    ->orWhere('u.id', 'between', [100, 200])
    ->groupBy(['id', 'username ASC'])
    ->having('u.username', '=', '1')
    ->having('u.username', '=', '2')
    ->having(function(SelectQuery $query) {
        $query->having('x', '<>', '2');
        $query->having('y', '=', null);
        $query->having('z', '<>', '5');
        $query->orHaving(function(SelectQuery $query) {
            $query->having('w.id', '=', new RawValue('p.id'));
            $query->orHaving('z.id', '=', new RawValue('l.id'));
        });
    })
    ->orderBy(['id ASC', 'username DESC'])
    ->limit(0, 10);

echo $select->getSql(); // "SELECT ..."
```

Fetch all rows:

```php
$rows = $select->execute()->fetchAll();
print_r($rows);
```

Fetch only the first row:

```php
$row = $select->execute()->fetch();
```

Fetch only the first value of the first row:

```php
$value = $select->execute()->fetchColumn(0);
```

## Testing

``` bash
$ composer test
```

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
[Composer]: http://getcomposer.org/
[PHPUnit]: http://phpunit.de/

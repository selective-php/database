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

* PHP 7.x
* MySQL

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

$db = new \Odan\Database\Connection($dsn, $username, $password, $options);

$users = $db->select()
    ->columns('id', 'username', 'email')
    ->from('users')
    ->execute()
    ->fetchAll();
    
foreach ($users as $user) {
    var_dump($user);
}
```

## Documentation

The database query builder provides a convenient, fluent interface to creating and running database queries. It can be used to perform most database operations in your application, and works on all supported database systems.

For more details how to build queries read the **[documentation](https://odan.github.io/database/)**.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
[Composer]: http://getcomposer.org/
[PHPUnit]: http://phpunit.de/

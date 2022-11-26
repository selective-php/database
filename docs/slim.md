---
layout: default
title: Slim 4 integration
nav_order: 8
---

# Slim 4 integration

## Requirements

* PHP 8+
* MySQL 5.7+
* [A Slim 4 application](https://odan.github.io/2019/11/05/slim4-tutorial.html)
* A DI container (PSR-11), e.g. PHP-DI

## Installation

To add the query builder to your application, run:

```
composer require selective/database
```

## Configuration

Add the database settings to Slim’s settings array, e.g `config/settings.php`:

```php
// Database settings
$settings['db'] = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'test',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'options' => [
        // Turn off persistent connections
        PDO::ATTR_PERSISTENT => false,
        // Enable exceptions
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Emulate prepared statements
        PDO::ATTR_EMULATE_PREPARES => true,
        // Set default fetch mode to array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // Set character set
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
    ],
];
```

Add the following container definitions into your `config/container.php` file:

```php
<?php

use Psr\Container\ContainerInterface;
use Selective\Config\Configuration;
use Selective\Database\Connection;
use Slim\App;

//...

return [

    // ...

    // Database connection
    Connection::class => function (ContainerInterface $container) {
        return new Connection($container->get(PDO::class));
    },

    PDO::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['db'];

        $driver = $settings['driver'];
        $host = $settings['host'];
        $dbname = $settings['database'];
        $username = $settings['username'];
        $password = $settings['password'];
        $charset = $settings['charset'];
        $flags = $settings['flags'];
        $dsn = "$driver:host=$host;dbname=$dbname;charset=$charset";

        return new PDO($dsn, $username, $password, $flags);
    },
];
```

### Usage

You can inject the `Connection` instance into your repository like this:

```php
<?php

namespace App\Domain\User\Repository;

use DomainException;
use Selective\Database\Connection;

final class UserReaderRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getUserById(int $userId): array
    {
        $query = $this->connection->select()->from('users');

        $query->columns(['id', 'username', 'email']);
        $query->where('id', '=', $userId);

        $row = $query->execute()->fetch() ?: [];

        if(!$row) {
            throw new DomainException(sprintf('User not found: %s', $userId));
        }

        return $row;
    }

}
```


**Next page:** [Select](selects.md)

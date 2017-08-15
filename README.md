# Database
 
 This is a simple skeleton project for Symfony that includes Plates, Sessions and Monolog.

[![Latest Version on Packagist](https://img.shields.io/github/release/odan/database.svg)](https://github.com/odan/database/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)
[![Build Status](https://travis-ci.org/odan/database.svg?branch=master)](https://travis-ci.org/odan/database)
[![Coverage Status](https://scrutinizer-ci.com/g/odan/database/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/odan/database/code-structure)
[![Quality Score](https://scrutinizer-ci.com/g/odan/database/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/odan/database/?branch=master)
[![Total Downloads](https://img.shields.io/packagist/dt/odan/database.svg)](https://packagist.org/packages/odan/database)


## Features

* Extended PDO connection
* SQL query builder (Aura.SqlQuery)
* Table manipulation
* Encryption and compression

## Installation

```shell
composer require odan/database
```

## Requirements

* PHP 7.0+, MySQL

## SQL Query Builder

This framework comes with [Aura.SqlQuery](https://github.com/auraphp/Aura.SqlQuery) as SQL query builder.

The database query builder provides a convenient, fluent interface to creating and running database queries. It can be used to perform most database operations in your application, and works on all supported database systems.

For more details how to build queries read the **[documentation](https://github.com/auraphp/Aura.SqlQuery/blob/3.x/docs/index.md)**.


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

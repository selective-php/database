---
layout: default
title: Inserts
nav_order: 4
---

## Inserts

Create an insert object:

```php
use Selective\Database\Connection;

$connection = new Connection($dsn, $username, $password, $options);
$query = $connection->insert();
```

### Insert A Single Row

The query builder also provides an insert method for inserting
records into the database table.

The insert method accepts an array of column names and values:

```php
$connection->insert()
    ->into('test')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();
```

You may even insert several records into the table with a single call
to insert by passing an array of arrays. Each array represents a
row to be inserted into the table:

```php
$connection->insert()
    ->into('test')->set([
        ['email' => 'daniel@example.com', 'votes' => 0],
        ['email' => 'john@example.com', 'votes' => 0]
    ])->execute();
```

### Auto-Incrementing IDs

If the table has an auto-incrementing id,
use the insertGetId method to insert a record and then retrieve the ID:

```php
$userId = $connection->insert()
    ->into('users')
    ->insertGetId(['email' => 'john@example.com', 'votes' => 0]);
```

Another way to get the last inserted ID:

```php
$connection->insert()
    ->into('users')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();

$userId = $connection->lastInsertId();
```

### Number of rows affected by the last statement

Sometimes you need more then just the last inserted ID, for example the number of affected rows.
You can find this information in the Statement object:

```php
$stmt = $connection->insert()
    ->into('users')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->prepare();

$stmt->execute();
$rowCount = $stmt->rowCount(); // 1
```

**Next page:** [Updates](updates.md)

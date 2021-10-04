---
layout: default
title: Deletes
nav_order: 6
---


## Deletes

Create a delete object:

```php
use Selective\Database\Connection;

$connection = new Connection($dsn, $username, $password, $options);
$delete = $connection->delete();
```

The query builder may also be used to delete records from the
table via the delete method. You may constrain delete
statements by adding where clauses before calling the delete method:


```php
// DELETE FROM `users`
$connection->delete()->from('users')->execute();

// DELETE FROM `users` WHERE `votes` > '100'
$connection->delete()->from('users')->where('votes', '>', 100)->execute();
```

If you wish to truncate the entire table, which will remove
all rows and reset the auto-incrementing ID to zero,
you may use the truncate method:

```php
// TRUNCATE TABLE `users`
$connection->delete()
    ->from('users')
    ->truncate()
    ->execute();
```

### Order of Deletion

If the DELETE statement includes an ORDER BY clause, rows are deleted in the
order specified by the clause. This is useful primarily in conjunction with LIMIT.

```php
$connection->delete()
    ->from('some_logs')
    ->where('username', '=', 'jcole')
    ->orderBy('created_at')
    ->limit(1)
    ->execute();
```

ORDER BY also helps to delete rows in an order required to avoid referential integrity violations.

### Delete Limit

The LIMIT clause places a limit on the number of rows that can be deleted.

```php
$connection->delete()
    ->from('users')
    ->limit(10)
    ->execute();
```

### Delete Low Priority

If you specify `LOW_PRIORITY`, the server delays execution of the
DELETE until no other clients are reading from the table.

This affects only storage engines that use only table-level
locking (such as MyISAM, MEMORY, and MERGE).

```php
$connection->delete()
    ->from('users')
    ->lowPriority()
    ->execute();
```

### Delete and ignore errors

The `IGNORE` modifier causes MySQL to ignore errors during the process of deleting rows.

(Errors encountered during the parsing stage are processed in the usual manner.)

Errors that are ignored due to the use of IGNORE are returned as warnings.

```php
$connection->delete()
    ->from('users')
    ->ignore()
    ->execute();
```

### Delete Quick modifier

For MyISAM tables, if you use the QUICK modifier, the storage engine
does not merge index leaves during delete, which may speed up some kinds of delete operations.

```php
$connection->delete()
    ->from('users')
    ->quick()
    ->execute();
```

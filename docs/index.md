# Documentation

* [Connection](#connection)
* [Select](#selects)
* [Insert](#inserts)
* [Update](#updates)
* [Delete](#deletes)
* [Schema](#schema)
* [Compression](#compression)

## Introduction

The database query builder provides a convenient, fluent interface to creating and running database queries. It can be used to perform most database operations in your application and works on all supported database systems.

The query builder uses PDO parameter binding to protect your application against SQL injection attacks. There is no need to clean strings being passed as bindings.

## Connection

Create a new database Connection:

```php
<?php

use Odan\Database\Connection;
use Odan\Database\QueryFactory;
use Odan\Database\RawExp;
use PDO;

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

// Create a query factory object
$query = new QueryFactory($connection);
```

## Selects

### Retrieving Results

#### Retrieving All Rows From A Table

You may use the `select()` method on the `QueryFactory` to begin a query. 
The table method returns a fluent query builder instance for 
the given table, allowing you to chain more constraints onto 
the query and then finally get the results using the get method:

```php
$stmt = $query->select()
    ->columns('id', 'username', 'email')
    ->from('users')
    ->query();
    
$rows = $stmt->fetchAll();
```

The PDO `fetch()` method returns an row containing the results 
where each result is an instance of the Array or PHP StdClass object. 
You may access each column's value by accessing the column as a property of the object:

```php
$stmt = $select->query();
while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
    echo $row->id;
}
```

#### Retrieving A Single Row From A Table

```php
$row = $select->query()->fetch();
```

#### Retrieving A Single Column From A Table

```php
$value = $select->query()->fetchColumn(0);
```

#### Distinct

The distinct method allows you to force the query to return distinct results:

```php
$select = $query->select()->distinct()->columns('id')->from('users');
```

#### Raw Expressions

Sometimes you may need to use a raw expression in a query. 
These expressions will be injected into the query as strings, 
so be careful not to create any SQL injection points! 

To create a raw expression, you may use the RawExp value object:

```php
$users = $query->select()
    ->columns(new RawExp('count(*) as user_count'), 'status')
    ->from('payments')
    ->where('status', '<>', 1)
    ->groupBy('status')
    ->query()
    ->fetchAll();
```

#### Aggregates

The query builder also provides a RawExp for aggregate methods 
such as count, max, min, avg, and sum. 

You may call any of these methods after constructing your query:

```php
$payments = $query->select()
    ->columns(new RawExp('MAX(amount)'), new RawExp('MIN(amount)'))
    ->from('payments')
    ->query()
    ->fetchAll();
```

### Joins

#### Inner Join Clause

The query builder may also be used to write join statements. 
To perform a basic "inner join", you may use the join method 
on a query builder instance. The first argument passed to 
the join method is the name of the table you need to join to, 
while the remaining arguments specify the column constraints 
for the join. Of course, as you can see, you can join to 
multiple tables in a single query:

```php
$users = $this->select()
    ->columns('users.*', 'contacts.phone', 'orders.price')
    ->from('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->join('orders', 'users.id', '=', 'orders.user_id')
    ->query()
    ->fetchAll();
```

#### Left Join Clause

If you would like to perform a "left join" instead of an "inner join", 
use the leftJoin method. The  leftJoin method has the same signature as the join method:

```php
$users = $this->select()
    ->from('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->query()
    ->fetchAll();
```

#### Cross Join Clause

To perform a "cross join" use the crossJoin method with the name of the table you wish to cross join to. 
Cross joins generate a cartesian product between the first table and the joined table:

```php
$users = $this->select()
    ->from('sizes')
    ->crossJoin('posts', 'users.id', '=', 'posts.user_id')
    ->query()
    ->fetchAll();
```

#### Advanced Join Clauses

You may also specify more advanced join clauses. 
To get started, pass a Closure as the second argument into 
the join method. The Closure will receive a JoinClause object 
which allows you to specify constraints on the join clause:

* todo

### Unions

The query builder also provides a quick way to "union" two queries together. 
For example, you may create an initial query and use the 
union method to union it with a second query:

* todo

#### Where Clauses

Simple Where Clauses

You may use the where method on a query builder instance 
to add where clauses to the query. The most basic call 
to where requires three arguments. The first argument is 
the name of the column. The second argument is an operator, 
which can be any of the database's supported operators. 

Finally, the third argument is the value to evaluate against the column.

For example, here is a query that verifies the value 
of the "votes" column is equal to 100:

```php
$users = $query->select()->from('users')->where('votes', '=', 100)->query()->fetchAll();
```

Of course, you may use a variety of other operators when writing a where clause:

```php
$users = $query->select()->from('users')->where('votes', '>=', 100)->query()->fetchAll();
$users = $query->select()->from('users')->where('votes', '<>', 100)->query()->fetchAll();
$users = $query->select()->from('users')->where('name', 'like', 'D%')->query()->fetchAll();
```

You may also pass multiple AND conditions:

```php
$users = $query->select()->from('users')
    ->where('status', '=', 1)
    ->where('subscribed', '<>', 1)
    ->query()->fetchAll();
```

#### Or Statements

ou may chain where constraints together as well as add OR clauses to the query. 
The orWhere method accepts the same arguments as the where method:

```php
$users = $query->select()->from('users')
    ->where('votes', '>', 100)
    ->orWhere('name', '=', 'John')
    ->query()->fetchAll();
```

#### Additional Where Clauses

##### Between and not between

```php
$users = $query->select()->from('users')
    ->where('votes', 'between', [1, 100])
    ->query()->fetchAll();
```

```php
$users = $query->select()->from('users')
    ->where('votes', 'not between', [1, 100])
    ->query()->fetchAll();
```

##### In and not in

```php
$users = $query->select()->from('users')
    ->where('id', 'in', [1, 2, 3])
    ->query()->fetchAll();
```

```php
$users = $query->select()->from('users')
    ->where('votes', 'not in', [1, 2, 3])
    ->query()->fetchAll();
```

##### Is null and is not null

```php
$users = $query->select()->from('users')
    ->where('updated_at', 'is', null)
    ->query()->fetchAll();
```

```php
$users = $query->select()->from('users')
    ->where('updated_at', 'is not', null)
    ->query()->fetchAll();
```

If you use the '=' or '<>' for comparison and pass a null value you get the same result.

```php
$users = $query->select()->from('users')
    ->where('updated_at', '=', null) // IS NULL
    ->query()->fetchAll();
```

#### Where Column

```php
$users = $query->select()->from('users')
    ->where('users.id', '=', new RawExp('posts.user_id'))
    ->query()->fetchAll();
```

#### Complex Where Conditions

```php
$users = $query->select()
    ->columns('id', 'username')
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
    ->where(function (SelectQuery $query) {
        $query->where('t2.field', '=', '1');
        $query->where('t2.field2', '>', '1');
    })
    ->orWhere(function (SelectQuery $query) {
        $query->where('t.a', '<>', '2');
        $query->where('t.b', '=', null);
        $query->where('t.c', '>', '5');
        $query->orWhere(function (SelectQuery $query) {
            $query->where(new RawExp('a.id = b.id'));
            $query->orWhere(new RawExp('c.id = u.id'));
        });
    })
    ->query()
    ->fetchAll();
```

#### Where Raw

```php
$users = $query->select()->from('users')
    ->where(new RawExp('users.id = posts.user_id'))
    ->query()->fetchAll();
```

#### Order By

```php
$users = $query->select()->from('users')
    ->orderBy('updated_at ASC')
    ->query()->fetchAll();
```

#### Group By

```php
$users = $query->select()->from('users')
    ->groupBy('role')
    ->query()->fetchAll();
```

#### Limit and Offset

```php
$users = $query->select()->from('users')
    ->limit(10)
    ->query()->fetchAll();
```

```php
$users = $query->select()->from('users')
    ->limit(10)
    ->offset(25)
    ->query()->fetchAll();
```

#### Having

```php
$users = $query->select()
    ->from('users')
    ->groupBy('id', 'username ASC')
    ->having('u.username', '=', 'admin')
    ->query()
    ->fetchAll();
```

Complex having conditions:

```php
$users = $query->select()
    ->from('users')
    ->groupBy(['id', 'username ASC'])
    ->having('u.username', '=', 'admin')
    ->having('u.username', '=', 'max')
    ->having(function(SelectQuery $query) {
        $query->having('x', '<>', '2');
        $query->having('y', '=', null);
        $query->having('z', '<>', '5');
        $query->orHaving(function(SelectQuery $query) {
            $query->having(new RawExp('a.id = b.id'));
            $query->orHaving(new RawExp('c.id = u.id'));
        });
    })
    ->query()
    ->fetchAll();
```

## Inserts

### Insert A Single Row

The query builder also provides an insert method for inserting 
records into the database table. 

The insert method accepts an array of column names and values:

```php
$query->insert()->into('test')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();
```

You may even insert several records into the table with a single call 
to insert by passing an array of arrays. Each array represents a 
row to be inserted into the table:

```php
$query->insert()->into('test')->set([
        ['email' => 'daniel@example.com', 'votes' => 0],
        ['email' => 'john@example.com', 'votes' => 0]
    ])->execute();
```

### Auto-Incrementing IDs

If the table has an auto-incrementing id, 
use the insertGetId method to insert a record and then retrieve the ID:

```php
$userId = $this->insert()->into('test')->insertGetId(['email' => 'john@example.com', 'votes' => 0]);
```

Another way to get the last inserted ID:

```php
$insert = $this->insert()->into('users')->set(['email' => 'john@example.com', 'votes' => 0]);
$insert->execute();
$userId = $insert->lastInsertId();
```

Sometimes you need more then just the last inserted ID, for example the number of affected rows.
You can find all this informations in the PDO connection object:

```php
$query->insert()
    ->into('users')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();

// Use the connection object 
$newId = $connection->lastInsertId(); // 1
$rowCount = $connection->rowCount(); // 1
```


## Updates

Of course, in addition to inserting records into the database, 
the query builder can also update existing records using the update method. 

The update method, like the insert method, accepts an array of column 
and value pairs containing the columns to be updated. 

You may constrain the update query using where clauses:

```php
$query->update()->table('users')->set(['votes' => '1'])->where('id', '=', '1')->execute();
```

```php
$query->update()->table('users')
    ->set(['votes' => '1'])
    ->where('id', '=', '1')
    ->orWhere('id', '=', '2')
    ->execute();
```

### Get number of affected rows:

```php
$stmt = $query->update()->table('users')->set(['votes' => '1'])->where('id', '=', '1')->prepare();
$stmt->execute();
$affectedRowCount = $stmt->rowCount();
```

### Increment & Decrement

The query builder also provides convenient methods for incrementing or 
decrementing the value of a given column. This is simply a shortcut, 
providing a more expressive and terse interface compared to manually 
writing the update statement.

Both of these methods accept at least one argument: the column to modify. 
A second argument may optionally be passed to control the amount by 
which the column should be incremented or decremented:

```php
$query->update()->table('users')->increment('voted')->execute();
$query->update()->table('users')->increment('voted', 10)->execute();
$query->update()->table('users')->increment('voted', 1)->where('id', '=', 1)->execute();
```

```php
$query->update()->table('users')->decrement('voted', 1)->where('id', '=', 0)->execute();
```

Incrementing without the convenient methods:

```php
$query->update()
    ->table('users')
    ->set(['votes' => new RawExp('votes+1')])
    ->where('id', '=', '1')
    ->execute();
```

### Update Limit

The `limit` clause places a limit on the number of rows that can be updated.

```php
$query->update()->table('users')->set(['votes' => '1'])->limit(10)->execute();
```

### Update Low Priority

With the `LOW_PRIORITY ` modifier, execution of the UPDATE is delayed until no 
other clients are reading from the table. This affects only storage engines 
that use only table-level locking (such as MyISAM, MEMORY, and MERGE).

```php
$query->update()->table('users')->set(['votes' => '1'])->lowPriority()->execute();
```

### Update and ignore errors

With the `IGNORE` modifier, the update statement does not abort 
even if errors occur during the update. Rows for which duplicate-key 
conflicts occur on a unique key value are not updated. 

```php
$query->update()->table('users')->set(['votes' => '1'])->ignore()->execute();
```

### Update with order by

If an UPDATE statement includes an ORDER BY clause, 
the rows are updated in the order specified by the clause. 

```php
$query->update()->table('users')->set(['votes' => '1'])->orderBy('created_at DESC', 'id DESC')->execute();
```

## Deletes

The query builder may also be used to delete records from the 
table via the delete method. You may constrain delete 
statements by adding where clauses before calling the delete method:


```php
$query->delete()->from('users')->execute(); // DELETE FROM `users`
$query->delete()->from('users')->where('votes', '>', 100)->execute(); // DELETE FROM `users` WHERE `votes` > '100'
```

If you wish to truncate the entire table, which will remove 
all rows and reset the auto-incrementing ID to zero, 
you may use the truncate method:

```php
$query->delete()->from('users')->truncate()->execute(); // TRUNCATE TABLE `users`; 
```

### Order of Deletion

If the DELETE statement includes an ORDER BY clause, rows are deleted in the 
order specified by the clause. This is useful primarily in conjunction with LIMIT. 

```php
$query->delete()->from('some_logs')
    ->where('username', '=', 'jcole')
    ->orderBy('createt_at') 
    ->limit(1)
    ->execute();
```

ORDER BY also helps to delete rows in an order required to avoid referential integrity violations.

You cannot use ORDER BY or LIMIT in a multiple-table DELETE.

### Delete Limit

The LIMIT clause places a limit on the number of rows that can be deleted. 
These clauses apply to single-table deletes, but not multi-table deletes.

```php
$query->delete()->from('users')->limit(10)->execute();
```

### Delete Low Priority

If you specify `LOW_PRIORITY`, the server delays execution of the 
DELETE until no other clients are reading from the table. 

This affects only storage engines that use only table-level 
locking (such as MyISAM, MEMORY, and MERGE).

```php
$query->delete()->from('users')->lowPriority()->execute();
```

### Delete and ignore errors

The `IGNORE` modifier causes MySQL to ignore errors during the process of deleting rows. 

(Errors encountered during the parsing stage are processed in the usual manner.) 

Errors that are ignored due to the use of IGNORE are returned as warnings.

```php
$query->delete()->from('users')->ignore()->execute();
```

### Delete Quick modifier

For MyISAM tables, if you use the QUICK modifier, the storage engine 
does not merge index leaves during delete, which may speed up some kinds of delete operations.

```php
$query->delete()->from('users')->quick()->execute();
```

### Multi-Table Deletes

You can specify multiple tables in a DELETE statement to delete rows 
from one or more tables depending on the condition in the WHERE clause.

```
Not supported
```

## Table

```php
$table = new \Odan\Database\Table($connection);
```

Todo: Write more documentation

## Schema

```php
$schema = new \Odan\Database\Schema($connection);
```

### Current database

```php
$database = $schema->getDatabase();
```

### Change database

```php
$schema->setDatabase('my_database');
```

Todo: Write more documentation


## Compression

The compress and uncompress methods are compatible with MySQL COMPRESS.

```php
$compression = new \Odan\Database\Compression($connection);
```

### Compress

```php
$compressed = $compression->compress('data');
```

### Uncompress

```php
$uncompressed = $compression->uncompress('compressed data');
```

# Documentation

* [Connection](#connection)
* [Select](#selects)
* [Insert](#inserts)
* [Update](#updates)
* [Delete](#deletes)
* [Repository](repository.md)
* [Schema](schema.md)
* [Compression](compression.md)

## Introduction

The database query builder provides a convenient, fluent interface to creating and running database queries. 
It can be used to perform most database operations in your application and works on all supported database systems.

The query builder uses PDO parameter binding to protect your application against SQL injection attacks. 
There is no need to clean strings being passed as bindings.

## Connection

Create a new database Connection:

```php
<?php

use Odan\Database\Connection;
use Odan\Database\QueryFactory;
use Odan\Database\RawExp;

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

$db = new Connection($dsn, $username, $password, $options);
```

## Selects

Create a select query object with the connection object.

```php
$statement = $db->select()->from('users')->execute();
```
Creating a SelectQuery object manually:

```php
$select = new \Odan\Database\SelectQuery($db);
```

## Inspecting The Query

Getting the generated SQL string:

```php
echo $db->select()->from('users')->build();
```
Output:

```sql
SELECT * FROM `users`;
```

### Retrieving Results

#### Retrieving All Rows From A Table

You may use the `select()` method on the `QueryFactory` to begin a query. 
The table method returns a fluent query builder instance for 
the given table, allowing you to chain more constraints onto 
the query and then finally get the results using the get method:

```php
$stmt = $db->select()
    ->columns('id', 'username', 'email')
    ->from('users')
    ->execute();
    
$rows = $stmt->fetchAll();
```

The PDO `fetch()` method returns an row containing the results 
where each result is an instance of the Array or PHP StdClass object. 
You may access each column's value by accessing the column as a property of the object:

```php
$stmt = $db->select()->from('users')->execute();
while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
    echo $row->id;
}
```

#### Retrieving A Single Row From A Table

```php
$row = $db->select()->from('users')->execute()->fetch();
```

#### Retrieving A Single Column From A Table

```php
$value = $db->select()->from('users')->execute()->fetchColumn(0);
```

#### Distinct

The distinct method allows you to force the query to return distinct results:

```php
$users = $db->select()->distinct()->columns('id')->from('users')->execute()->fetchAll();
```

#### Raw Expressions

Sometimes you may need to use a raw expression in a query. 
These expressions will be injected into the query as strings, 
so be careful not to create any SQL injection points! 

To create a raw expression, you may use the RawExp value object:

```php
$users = $db->select()
    ->columns(new RawExp('count(*) as user_count'), 'status')
    ->from('payments')
    ->where('status', '<>', 1)
    ->groupBy('status')
    ->execute()
    ->fetchAll();
```

```sql
SELECT count(*) as user_count, `status` FROM `payments` WHERE `status` <> 1 GROUP BY `status`;
```

#### Aggregates

The query builder also provides a RawExp for aggregate methods 
such as count, max, min, avg, and sum. 

You may call any of these methods after constructing your query:

```php
$payments = $db->select()
    ->columns(new RawExp('MAX(amount)'), new RawExp('MIN(amount)'))
    ->from('payments')
    ->execute()
    ->fetchAll();
```

```sql
SELECT MAX(amount), MIN(amount) FROM `payments`;
```

#### Sub Selects

If you want to SELECT FROM a subselect, do so by passing a callback
function and define an alias for the subselect:

```php
$payments = $this->select()
    ->columns('id', function (SelectQuery $subSelect) {
        $subSelect->columns(new RawExp('MAX(payments.amount)'))
        ->from('payments')
        ->alias('max_amount');
    })
    ->from('test')
    ->execute()
    ->fetchAll();
```

```sql
SELECT `id`,(SELECT MAX(payments.amount) FROM `payments`) AS `max_amount` FROM `test`;
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
    ->execute()
    ->fetchAll();
```

```sql
SELECT `users`.*, `contacts`.`phone`, `orders`.`price` 
FROM `users`
INNER JOIN `contacts` ON `users`.`id` = `contacts`.`user_id`
INNER JOIN `orders` ON `users`.`id` = `orders`.`user_id`;
```

#### Left Join Clause

If you would like to perform a "left join" instead of an "inner join", 
use the leftJoin method. The  leftJoin method has the same signature as the join method:

```php
$users = $this->select()
    ->from('users')
    ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
    ->execute()
    ->fetchAll();
```

```sql
SELECT *
FROM `users`
LEFT JOIN `posts` ON `users`.`id` = `posts`.`user_id`;
```

#### Cross Join Clause

To perform a "cross join" use the crossJoin method with the name of the table you wish to cross join to. 
Cross joins generate a cartesian product between the first table and the joined table:

```php
$users = $this->select()
    ->from('sizes')
    ->crossJoin('posts', 'users.id', '=', 'posts.user_id')
    ->execute()
    ->fetchAll();
```

```sql
SELECT *
FROM `users`
CROSS JOIN `posts` ON `users`.`id` = `posts`.`user_id`;
```

#### Advanced Join Clauses

You may also specify more advanced join clauses. 
To get started, pass a Closure as the second argument into 
the join method. The Closure will receive a JoinClause object 
which allows you to specify constraints on the join clause:

```
Not supported.
```

### Unions

The query builder also provides a quick way to "union" two queries together. 
For example, you may create an initial query and use the 
`union()`, `unionAll()` and `unionDistinct() `method to union it with a second query:

```php
$select = $db->select()->columns('id')->from('table1');
$select2 = $this->select()->columns('id')->from('table2');
$select->union($select2);
```

```sql
SELECT `id` FROM `table1` UNION SELECT `id` FROM `table2`;
```

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
$users = $db->select()->from('users')->where('votes', '=', 100)->execute()->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` = 100;
```

Of course, you may use a variety of other operators when writing a where clause:

```php
$users = $db->select()->from('users')->where('votes', '>=', 100)->execute()->fetchAll();
$users = $db->select()->from('users')->where('votes', '<>', 100)->execute()->fetchAll();
$users = $db->select()->from('users')->where('name', 'like', 'D%')->execute()->fetchAll();
```

You may also pass multiple AND conditions:

```php
$users = $db->select()
    ->from('users')
    ->where('status', '=', 1)
    ->where('subscribed', '<>', 1)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `status` = '1' AND `subscribed` <> '1';
```

#### Or Statements

ou may chain where constraints together as well as add OR clauses to the query. 
The orWhere method accepts the same arguments as the where method:

```php
$users = $db->select()
    ->from('users')
    ->where('votes', '>', 100)
    ->orWhere('name', '=', 'John')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` > '100' OR `name` = 'John';
```

#### Additional Where Clauses

##### Between and not between

```php
$users = $db->select()
    ->from('users')
    ->where('votes', 'between', [1, 100])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` BETWEEN '1' AND '100';
```
 

```php
$users = $db->select()
    ->from('users')
    ->where('votes', 'not between', [1, 100])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` NOT BETWEEN '1' AND '100';
```

##### In and not in

```php
$users = $db->select()
    ->from('users')
    ->where('id', 'in', [1, 2, 3])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `id` IN ('1', '2', '3');
```

 

```php
$users = $db->select()
    ->from('users')
    ->where('votes', 'not in', [1, 2, 3])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `id` NOT IN ('1', '2', '3');
```

##### Is null and is not null

```php
$users = $db->select()
    ->from('users')
    ->where('updated_at', 'is', null)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `updated_at` IS NULL;
```

 

```php
$users = $db->select()
    ->from('users')
    ->where('updated_at', 'is not', null)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `updated_at` IS NOT NULL;
```

If you use the '=' or '<>' for comparison and pass a null value you get the same result.

```php
$users = $db->select()
    ->from('users')
    ->where('updated_at', '=', null) // IS NULL
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `updated_at` IS NULL;
```


#### Where Column

The whereColumn method may be used to verify that two columns are equal:

```php
$users = $db->select()
    ->from('users')
    ->whereColumn('users.id', '=', 'posts.user_id')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `users`.`id` = `posts`.`user_id`;
```

The whereColumn method can also be called multiple times to add multiple conditions. 
These conditions will be joined using the and operator:

```php
$users = $db->select()
    ->from('users')
    ->whereColumn('first_name', '=', 'last_name')
    ->whereColumn('updated_at', '=', 'created_at')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * 
FROM `users` 
WHERE `first_name` = `last_name`
AND `updated_at` = `created_at`;
```

#### Complex Where Conditions

```php
$users = $db->select()
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
        $db->where('t2.field', '=', '1');
        $db->where('t2.field2', '>', '1');
    })
    ->orWhere(function (SelectQuery $query) {
        $db->where('t.a', '<>', '2');
        $db->where('t.b', '=', null);
        $db->where('t.c', '>', '5');
        $db->orWhere(function (SelectQuery $query) {
            $db->where(new RawExp('a.id = b.id'));
            $db->orWhere(new RawExp('c.id = u.id'));
        });
    })
    ->execute()
    ->fetchAll();
```

#### Where Raw

```php
$users = $db->select()
    ->from('users')
    ->where(new RawExp('users.id = posts.user_id'))
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE users.id = posts.user_id;
```

#### Order By

```php
$users = $db->select()
    ->from('users')
    ->orderBy('updated_at ASC')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` ORDER BY `updated_at` ASC;
```

#### Group By

```php
$users = $db->select()
    ->from('users')
    ->groupBy('role')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` GROUP BY `role`;
```

#### Limit and Offset

```php
$users = $db->select()
    ->from('users')
    ->limit(10)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` LIMIT 10;
```

 

```php
$users = $db->select()
    ->from('users')
    ->limit(10)
    ->offset(25)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` LIMIT 25, 10;
```

#### Having

```php
$users = $db->select()
    ->from('users')
    ->groupBy('id', 'username ASC')
    ->having('username', '=', 'admin')
    ->execute()
    ->fetchAll();
```

```sql
SELECT * 
FROM `users` 
GROUP BY `id`, `username` ASC
HAVING `username` = 'admin';
```

Complex having conditions:

```php
$users = $db->select()
    ->from('users')
    ->groupBy(['id', 'username ASC'])
    ->having('u.username', '=', 'admin')
    ->having('u.username', '=', 'max')
    ->having(function(SelectQuery $query) {
        $db->having('x', '<>', '2');
        $db->having('y', '=', null);
        $db->having('z', '<>', '5');
        $db->orHaving(function(SelectQuery $query) {
            $db->having(new RawExp('a.id = b.id'));
            $db->orHaving(new RawExp('c.id = u.id'));
        });
    })
    ->execute()
    ->fetchAll();
```

## Inserts

Create a insert object:

```php
use Odan\Database\Connection;

$db = new Connection($dsn, $username, $password, $options);
$insert = $db->insert();
```

### Insert A Single Row

The query builder also provides an insert method for inserting 
records into the database table. 

The insert method accepts an array of column names and values:

```php
$db->insert()
    ->into('test')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();
```

You may even insert several records into the table with a single call 
to insert by passing an array of arrays. Each array represents a 
row to be inserted into the table:

```php
$db->insert()
    ->into('test')->set([
        ['email' => 'daniel@example.com', 'votes' => 0],
        ['email' => 'john@example.com', 'votes' => 0]
    ])->execute();
```

### Auto-Incrementing IDs

If the table has an auto-incrementing id, 
use the insertGetId method to insert a record and then retrieve the ID:

```php
$userId = $db->insert()->into('users')->insertGetId(['email' => 'john@example.com', 'votes' => 0]);
```

Another way to get the last inserted ID:

```php
$db->insert()
    ->into('users')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->execute();
    
$userId = $db->lastInsertId();
```

### Number of rows affected by the last statement

Sometimes you need more then just the last inserted ID, for example the number of affected rows.
You can find this information in the Statement object:

```php
$stmt = $db->insert()
    ->into('users')
    ->set(['email' => 'john@example.com', 'votes' => 0])
    ->prepare();
    
$stmt->execute();
$rowCount = $stmt->rowCount(); // 1
```

## Updates

Create a update object:

```php
use Odan\Database\Connection;

$db = new Connection($dsn, $username, $password, $options);
$update = $db->update();
```

Of course, in addition to inserting records into the database, 
the query builder can also update existing records using the update method. 

The update method, like the insert method, accepts an array of column 
and value pairs containing the columns to be updated. 

You may constrain the update query using where clauses:

```php
$status = $db->update()
    ->table('users')
    ->set(['votes' => '1'])
    ->where('id', '=', '1')
    ->execute();
```

```php
$db->update()
    ->table('users')
    ->set(['votes' => '1'])
    ->where('id', '=', '1')
    ->orWhere('id', '=', '2')
    ->execute();
```

### Get number of affected rows:

```php
$stmt = $db->update()->table('users')->set(['votes' => '1'])->where('id', '=', '1')->prepare();
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
$db->update()->table('users')->increment('voted')->execute();
$db->update()->table('users')->increment('voted', 10)->execute();
$db->update()->table('users')->increment('voted', 1)->where('id', '=', 1)->execute();
```

```php
$db->update()->table('users')->decrement('voted', 1)->where('id', '=', 1)->execute();
```

Incrementing without the convenient methods:

```php
$db->update()
    ->table('users')
    ->set(['votes' => new RawExp('votes+1')])
    ->where('id', '=', '1')
    ->execute();
```

### Update Limit

The `limit` clause places a limit on the number of rows that can be updated.

```php
$db->update()->table('users')->set(['votes' => '1'])->limit(10)->execute();
```

### Update Low Priority

With the `LOW_PRIORITY ` modifier, execution of the UPDATE is delayed until no 
other clients are reading from the table. This affects only storage engines 
that use only table-level locking (such as MyISAM, MEMORY, and MERGE).

```php
$db->update()->table('users')->set(['votes' => '1'])->lowPriority()->execute();
```

### Update and ignore errors

With the `IGNORE` modifier, the update statement does not abort 
even if errors occur during the update. Rows for which duplicate-key 
conflicts occur on a unique key value are not updated. 

```php
$db->update()->table('users')->set(['votes' => '1'])->ignore()->execute();
```

### Update with order by

If an UPDATE statement includes an ORDER BY clause, 
the rows are updated in the order specified by the clause. 

```php
$db->update()->table('users')->set(['votes' => '1'])->orderBy('created_at DESC', 'id DESC')->execute();
```

## Deletes

Create a delete object:

```php
use Odan\Database\Connection;

$db = new Connection($dsn, $username, $password, $options);
$delete = $db->delete();
```

The query builder may also be used to delete records from the 
table via the delete method. You may constrain delete 
statements by adding where clauses before calling the delete method:


```php
$db->delete()->from('users')->execute(); // DELETE FROM `users`
$db->delete()->from('users')->where('votes', '>', 100)->execute(); // DELETE FROM `users` WHERE `votes` > '100'
```

If you wish to truncate the entire table, which will remove 
all rows and reset the auto-incrementing ID to zero, 
you may use the truncate method:

```php
$db->delete()->from('users')->truncate()->execute(); // TRUNCATE TABLE `users`; 
```

### Order of Deletion

If the DELETE statement includes an ORDER BY clause, rows are deleted in the 
order specified by the clause. This is useful primarily in conjunction with LIMIT. 

```php
$db->delete()->from('some_logs')
    ->where('username', '=', 'jcole')
    ->orderBy('created_at') 
    ->limit(1)
    ->execute();
```

ORDER BY also helps to delete rows in an order required to avoid referential integrity violations.

### Delete Limit

The LIMIT clause places a limit on the number of rows that can be deleted. 

```php
$db->delete()->from('users')->limit(10)->execute();
```

### Delete Low Priority

If you specify `LOW_PRIORITY`, the server delays execution of the 
DELETE until no other clients are reading from the table. 

This affects only storage engines that use only table-level 
locking (such as MyISAM, MEMORY, and MERGE).

```php
$db->delete()->from('users')->lowPriority()->execute();
```

### Delete and ignore errors

The `IGNORE` modifier causes MySQL to ignore errors during the process of deleting rows. 

(Errors encountered during the parsing stage are processed in the usual manner.) 

Errors that are ignored due to the use of IGNORE are returned as warnings.

```php
$db->delete()->from('users')->ignore()->execute();
```

### Delete Quick modifier

For MyISAM tables, if you use the QUICK modifier, the storage engine 
does not merge index leaves during delete, which may speed up some kinds of delete operations.

```php
$db->delete()->from('users')->quick()->execute();
```

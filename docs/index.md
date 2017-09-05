# Documentation

* [Connection](#connection)
* [Select](#select)
* [Insert](#insert)
* [Update](#update)
* [Delete](##delete)
* [Schema](#schema)

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

## Select

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

## Insert

* todo

## Update

* todo

## Delete

* todo

## Table

* todo

## Schema

* todo

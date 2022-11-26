---
layout: default
title: Selects
nav_order: 3
---

## Selects

Create a select query object with the connection object.

```php
use Selective\Database\Connection;

$connection = new Connection($pdo);

$query = $connection->select();
```

Creating a SelectQuery object manually:

```php
use Selective\Database\Connection;

$connection = new Connection($pdo);

$query = new \Selective\Database\SelectQuery($connection);
```

## Inspecting The Query

Getting the generated SQL string:

```php
echo $connection->select()->from(['user' => 'users'])->build();
```
Output:

```sql
SELECT * FROM `users` as `user`;
```

### Retrieving Results

#### Retrieving All Rows From A Table

You may use the `select()` method of the `Connection` object to begin a query.
The table method returns a fluent query builder instance for
the given table, allowing you to chain more constraints onto
the query and then finally get the results using the get method:

```php
$query = $connection->select()->from('users');
$query->columns(['id', 'username', 'email']);

$rows = $query->execute()->fetchAll() ?: [];
```

The PDO `fetch()` method returns an row containing the results
where each result is an instance of the Array or PHP `stdClass` object.
You may access each column's value by accessing the column as a property of the object:

```php
$statement = $connection->select()->from('users')->execute();

while($row = $statement->fetch(PDO::FETCH_OBJ)) {
    echo $row->id;
}
```

#### Retrieving A Single Row From A Table

```php
$row = $connection->select()->from('users')->execute()->fetch();
```

#### Retrieving A Single Column From A Table

```php
$value = $connection->select()->from('users')->execute()->fetchColumn(0);
```

#### Distinct

The distinct method allows you to force the query to return distinct results:

```php
$query = $connection->select()->from('users')->distinct();

$query->columns(['id']);

$rows = $query->execute()->fetchAll();
```

#### Columns

Select columns by name:

```php
$query = $connection->select()->from('users');

$query->columns(['id', 'username', ['first_name' => 'firstName']]);

$rows = $query->execute()->fetchAll();
```

```sql
SELECT `id`,`username`,`first_name` AS `firstName` FROM `users`;
```

Select columns with an array:

```php
$query = $connection->select()->from('test');

$query->columns(['id', 'first_name', 'tablename.fieldname']);

$rows = $query->execute()->fetchAll();
```

Select columns with alias:

```php
$query = $connection->select()->from('test');

$query->columns([
    'firstName' => 'first_name',
    'lastName' => 'last_name',
    'fieldName' => 'tablename.fieldname'
]);

$rows = $query->execute()->fetchAll();
```

Select columns with alias as array:

```php
$query = $this->select()->from('test');

$query->columns([
    'id',
    'username',
    'firstName' => 'first_name',
    'last_name' => 'test.last_name',
    'email' => 'database.test.email',
    'value' => $query->raw('CONCAT("1","2")')
]);
```

```sql
SELECT
  `id`,
  `username`,
  `first_name` AS `firstName`,
  `test`.`last_name` AS `last_name`,
  `database`.`test`.`email` AS `email`,
  CONCAT("1","2") AS `value`
FROM
  `test`;
```
Add fields one after another:

```php
$query = $connection->select()
    ->columns(['first_name'])
    ->from('users');

$rows = $query->columns(['last_name', 'email'])
    ->execute()
    ->fetchAll();
```

```sql
SELECT `first_name`,`last_name`,`email` FROM `users`;
```

#### Sub Selects

If you want to SELECT FROM a subselect, do so by passing a callback
function and define an alias for the subselect:

```php
$query = $connection->select()->from('test');

$query->columns([
    'id',
    function (SelectQuery $subSelect) {
        $subSelect->columns($subSelect->raw('MAX(payments.amount)'))
           ->from('payments')
           ->alias('max_amount');
    }
]);

$rows = $query->execute()->fetchAll() ?: [];
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
$query = $connection->select()->from('users');

$query->columns(['users.*', 'contacts.phone', 'orders.price']);

$query->join('contacts', 'users.id', '=', 'contacts.user_id');
$query->join('orders', 'users.id', '=', 'orders.user_id');

$rows = $query->execute()->fetchAll() ?: [];
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
$query = $connection->select()->from('users');

$query->leftJoin('posts', 'users.id', '=', 'posts.user_id');

$rows = $query->execute()->fetchAll() ?: [];
```

```sql
SELECT *
FROM `users`
LEFT JOIN `posts` ON `users`.`id` = `posts`.`user_id`;
```

#### Cross Join Clause

From the [MySQL JOIN](https://dev.mysql.com/doc/refman/5.7/en/nested-join-optimization.html) docs:

> In MySQL, CROSS JOIN is syntactically equivalent to INNER JOIN; they can replace each other.
> In standard SQL, they are not equivalent. INNER JOIN is used with an ON clause; CROSS JOIN is used otherwise.

In MySQL Inner Join and Cross Join yielding the same result.

Please use the [join](#inner-join-clause) method.

#### Advanced Join Clauses

You may also specify more advanced join clauses.
To get started, pass a (raw) string as the second argument into
the `joinRaw` and `leftJoinRaw` method.

```php
$query = $connection->select()->from(['u' => 'users']);

$query->joinRaw(['p' => 'posts'], 'p.user_id=u.id AND u.enabled=1 OR p.published IS NULL');

$rows = $query->execute()->fetchAll() ?: [];
```

```sql
SELECT `id` FROM `users` AS `u`
INNER JOIN `posts` AS `p` ON (p.user_id=u.id AND u.enabled=1 OR p.published IS NULL);
```

### Unions

The query builder also provides a quick way to "union" two queries together.
For example, you may create an initial query and use the
`union()`, `unionAll()` and `unionDistinct() `method to union it with a second query:

```php
$select = $connection->select()
    ->from('table1')
    ->columns(['id']);

$select2 = $connection->select()
    ->from('table2')
    ->columns(['id']);

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
$rows = $connection->select()
    ->from('users')
    ->where('votes', '=', 100)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` = 100;
```

Of course, you may use a variety of other operators when writing a where clause:

```php
$rows = $connection->select()
    ->from('users')
    ->where('votes', '>=', 100)
    ->execute()
    ->fetchAll();

$rows = $connection->select()
    ->from('users')
    ->where('votes', '<>', 100)
    ->execute()
    ->fetchAll();

$rows = $connection->select()
    ->from('users')
    ->where('name', 'like', 'D%')
    ->execute()
    ->fetchAll();
```

You may also pass multiple AND conditions:

```php
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
    ->from('users')
    ->where('votes', 'between', [1, 100])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `votes` BETWEEN '1' AND '100';
```


```php
$rows = $connection->select()
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
$rows = $connection->select()
    ->from('users')
    ->where('id', 'in', [1, 2, 3])
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `id` IN ('1', '2', '3');
```

```php
$rows = $connection->select()
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
$rows = $connection->select()
    ->from('users')
    ->where('updated_at', 'is', null)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE `updated_at` IS NULL;
```

```php
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
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
            $query->where($query->raw('a.id = b.id'));
            $query->orWhere($query->raw('c.id = u.id'));
        });
    })
    ->execute()
    ->fetchAll();
```

#### Where Raw

Using whereRaw:

```php
$query = $connection->select()
    ->columns('id', 'username')
    ->from('users')
    ->whereRaw('status <> 1');

$rows = $query->execute()->fetchAll();
```

```sql
SELECT `id`, `username` FROM `users` WHERE status <> 1;
```

Using a raw expression:

```php
$query = $connection->select();
$rows = $query->from('users')
    ->where($query->raw('users.id = posts.user_id'))
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` WHERE users.id = posts.user_id;
```

#### Order By

```php
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
    ->from('users')
    ->limit(10)
    ->execute()
    ->fetchAll();
```

```sql
SELECT * FROM `users` LIMIT 10;
```



```php
$rows = $connection->select()
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
$rows = $connection->select()
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
$rows = $connection->select()
    ->from('users')
    ->groupBy(['id', 'username ASC'])
    ->having('u.username', '=', 'admin')
    ->having('u.username', '=', 'max')
    ->having(function(SelectQuery $query) {
        $query->having('x', '<>', '2');
        $query->having('y', '=', null);
        $query->having('z', '<>', '5');
        $query->orHaving(function(SelectQuery $query2) {
            $query2->having($query2->raw('a.id = b.id'));
            $query2->orHaving($query2->raw('c.id = u.id'));
        });
    })
    ->execute()
    ->fetchAll();
```

### Using SQL Functions

A number of commonly used functions can be created with the `func()` method.

You may call any of these methods after constructing your query:

* sum() Calculate a sum. The arguments will be treated as literal values.
* avg() Calculate an average. The arguments will be treated as literal values.
* min() Calculate the min of a column. The arguments will be treated as literal values.
* max() Calculate the max of a column. The arguments will be treated as literal values.
* count() Calculate the count. The arguments will be treated as literal values.
* now() Returns a Expression representing a call that will return the current date and time (ISO).

Example:

```php
$query = $connection->select()->from('payments');
$query->columns([$query->func()->sum('amount')->alias('sum_amount')]);

$rows = $query->execute()->fetchAll() ?: [];
```

```sql
SELECT SUM(`amount`) AS `sum_amount` FROM `payments`;
```

#### Using custom SQL Functions

*This new feature is under construction*

Whenever you're missing support for some vendor specific function,
please use plain SQL templating:

{% raw %}
```php
$query->func()->custom('substring_index(%s, %s, %s)', $string, $delimiter, $number);
```
{% endraw %}

### Raw Expressions

Sometimes you may need to use a raw expression in a query.

> These expressions will be injected into the query as strings,
so be careful not to create any SQL injection!

To create a raw expression, you can use the `raw` method:

```php
$query = $connection->select()->from('payments');

$query->columns([$query->raw('count(*) AS user_count'), 'status']);

$query->where('status', '<>', 1);
$query->groupBy('status');

$rows = $query->execute()->fetchAll() ?: [];
```
Output:
```sql
SELECT count(*) AS user_count, `status` FROM `payments` WHERE `status` <> 1 GROUP BY `status`;
```

Example 2:

```php
$query = $connection->select()->from('payments');
$query->columns([$query->raw('count(*) AS user_count'), 'status']);

$rows = $query->execute()->fetchAll() ?: [];
```

Output:

```sql
SELECT count(*) AS user_count,`status` FROM `payments`;
```

Example 3:

```php
$query = $connection->select()->from('payments');

$query = $query->columns([$query->raw('MAX(amount)'), $query->raw('MIN(amount)')]);

$rows = $query->execute()->fetchAll() ?: [];
```

Output:

```sql
SELECT MAX(amount), MIN(amount) FROM `payments`;
```

**Next page:** [Inserts](inserts.md)

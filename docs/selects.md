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
echo $db->select()->from('users AS user')->build();
```
Output:

```sql
SELECT * FROM `users` as `u`;
```

### Retrieving Results

#### Retrieving All Rows From A Table

You may use the `select()` method of the `Connection` object to begin a query. 
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

#### Columns

Select columns by name:

```php
$this->select()->columns('id', 'username', 'first_name AS firstName')->from('users')
```

```sql
SELECT `id`,`username`,`first_name` AS `firstName` FROM `users`;
```

Select columns by array:

```php
$db->select()->columns(['id', 'first_name', 'tablename.fieldname']);
```

Select columns with alias:

```php
$db->select()->columns('first_name AS firstName', 'last_name AS lastName', 'tablename.fieldname as fieldName');
```

Add fields one after another:

```php
$query = $db->select()->columns('first_name')->from('users');
$query->columns('last_name', 'email');
```

```sql
SELECT `first_name`,`last_name`,`email` FROM `users`;
```

#### Raw Expressions

Sometimes you may need to use a raw expression in a query. 
These expressions will be injected into the query as strings, 
so be careful not to create any SQL injection points! 

To create a raw expression, you may use the RawExp value object:

```php
$users = $db->select()
    ->columns(new RawExp('count(*) AS user_count'), 'status')
    ->from('payments')
    ->where('status', '<>', 1)
    ->groupBy('status')
    ->execute()
    ->fetchAll();
```

```sql
SELECT count(*) AS user_count, `status` FROM `payments` WHERE `status` <> 1 GROUP BY `status`;
```

Using whereRaw:

```php
$query = $db->select()
    ->columns('id', 'username')
    ->from('users')
    ->whereRaw('status <> 1');
    
$users = $query->execute()->fetchAll();
```

```sql
SELECT `id`, `username` WHERE status <> 1 FROM `users`;
```

Creating raw expressions with the function builder:

```php
$query = $db->select();
$query->columns($query->raw('count(*) AS user_count'), 'status');
$query->from('payments');
```

```sql
SELECT count(*) AS user_count,`status` FROM `payments`;
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
$users = $this->select()
    ->from('users AS u')
    ->joinRaw('posts AS p', 'p.user_id=u.id AND u.enabled=1 OR p.published IS NULL')
    ->execute()
    ->fetchAll();
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
        $query->having('x', '<>', '2');
        $query->having('y', '=', null);
        $query->having('z', '<>', '5');
        $query->orHaving(function(SelectQuery $query2) {
            $query2->having(new RawExp('a.id = b.id'));
            $query2->orHaving(new RawExp('c.id = u.id'));
        });
    })
    ->execute()
    ->fetchAll();
```

### Using SQL Functions

A number of commonly used functions can be created with the func() method:

* sum() Calculate a sum. The arguments will be treated as literal values.
* avg() Calculate an average. The arguments will be treated as literal values.
* min() Calculate the min of a column. The arguments will be treated as literal values.
* max() Calculate the max of a column. The arguments will be treated as literal values.
* count() Calculate the count. The arguments will be treated as literal values.
* now() Returns a Expression representing a call that will return the current date and time (ISO).

Example:

```php
$query = $db->select()->from('payments');
$query->columns($query->func()->sum('amount')->alias('sum_amount'));
```

```sql
SELECT SUM(`amount`) AS `sum_amount` FROM `payments`;
```

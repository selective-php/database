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
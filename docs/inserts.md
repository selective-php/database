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
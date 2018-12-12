# Introduction

Database Abstraction Layer for PHP.

Created to be simply as possible tool for creating and running SQL queries. Currently supports mysql and postgresql drivers.

# Requirements

PHP7, mysqli extension for Mysql support. pgsql extension for postgres..

# Installation

Install it via composer:

```
composer require zobaken/daltron
```

# Configuration

First we need to set configuration:

```php
\Dal\Dal::setConfiguration([
    'host' => '192.168.99.100',
    'user' => 'test',
    'password' => 'test',
    'dbname' => 'test',
    'driver' => 'mysql',
]);
```

It is possible to load configuration from file. For example
'config.php' can look like:

```php
<?php

return [
    'host' => '192.168.99.100',
    'user' => 'test',
    'password' => 'test',
    'dbname' => 'test',
    'driver' => 'mysql',
];
```

Then we load it like this:

```php
\Dal\Dal::loadConfiguration('config.php');
```

# Query builder

Simplest query will look like:

```php
$rows = db()->query('SELECT * FROM users')->fetchAllAssoc();
```

Lets try something more complex. Builder mimics SQL syntax, so nothing new to learn:

```php
$rows = db()
    ->select('*')
    ->from('users')
    ->where('created_ts = ?', $time)
    ->fetchAllAssoc();
```

Every "unknown" method of query, like `select` method in this example adds term to
the SQL request. All parameters mapped with `?` placeholders, that are not integer, 
are escaped and surrounded by quotes.

The previous example is equivalent to:

```php
$query = sprintf("SELECT * FROM users WHERE created_ts < '%s'",
    mysqli_real_escape_string($connection, $time)
);
$result = mysqli_query($connection, $query);
$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
```

Here is a typical situation when query conditions depends on user input:

```php
$rows = db()->select('*')
    ->from('users')
    ->where('true')
    ->ifQuery($timeFrom, 'AND created_ts >= ?', $timeFrom)
    ->ifQuery($timeTo, 'AND created_ts < ?', $timeTo)
    ->ifQuery($order && $orderDirection, 'ORDER BY #? !?', $order, $orderDirection)
    ->ifQuery($limit, 'LIMIT ?', $limit)
    ->ifQuery($offset, 'OFFSET ?', $offset)
    ->fetchAllAssoc();
```

Additional conditions are added only when the first parameter of `ifQuery` is
true. `#?` placeholder is used for field name escaping. In this example we assume
that `$orderDirection` equals `ASC` or `DESC` and `!?` placeholder does not escape the value,
use it with caution!

Insert and update example (we assume that id field is autoincrement):

```php
// Insert query
$row = [
    'name' => 'Peter Userman',
    'created_ts' => dbtime(),
    'hash' => password_hash('REMEMBERME1', PASSWORD_DEFAULT),
];

$id = db()->insertRow('users', $row)
    ->exec(true);

// Update query
db()->update('users')
    ->set('hash = ?', '')
    ->where('id = ?', $id)
    ->exec();

// User needs to know
printf("Updated %d row(s)\n", db()->affectedRows());
```

Method `insertRow` is a shortcut for insert request.
`dbtime` function without parameters returns current time in format `Y-m-d H:i:s`.
Passing `true` to `exec` method we ask it to return last inserted id.
Method `affectedRows` is used to get number of rows affected by previous query, obviously.

# Models

## Generation

To generate models we need already initialized database with existing data structure.
In this example we use provided script utilizing configuration file from examples above:

```php
vendor/bin/dbgen config.php model
```

Model classes will be created in "model" folder. If you need to create your own
model generator scripts (with blackjack and closures) you can write something like this:

```php
\Dal\Dal::loadConfiguration('config.php');
$generator = \Dal\Model\GeneratorFactory::createGenerator('model');
$generator->run();
```

You can modify your model classes and if data structure is changed - run generator again.
Model classes will not be overwritten, only their prototype classes. 

## Basic usage

Its your responsibility to load model classes using `spl_autoload_register` function or whatever method
you like.

Here is a an example of using model classes:

```php
// Create new object
$user = new User();
$user->name = 'Mike Swoloch';
$user->created_ts = dbtime();
$user->hash = md5('hash');
$user->id = $user->insert(true);
```

Passing `true` to `insert` we ask it to return last inserted id.

Next we will get object from database and update it:

```php
// Get object from database
$user = User::get($id);
if ($user) {
    // Update object
    $user->name = 'Mike Sweety';
    $user->update();
}
```

Simple as it!

Use `remove` to delete the object:

```php
// Delete object
$user->remove();
```

## Advanced model requests

We can get object by passing where condition to `findRow` method:

```php
$user = User::findRow('name = ?', 'Alexandr Flea');
```

Same for list of object using `find` method:

```php
$objects = User::find('created_ts < ?', dbtime('- 1 day'));
```

This will return objects created earlier then day ago.

You can pass not only where condition, but some later part of request.
It is possible to limit our request to return only 10 rows:

```php
$objects = User::find('created_ts < ? LIMIT ?', dbtime('- 1 day'), 10);
```

Also we can use regular query builder syntax. Here is slightly modified
request from example we had in somewhere above. Query will return an
array of `User` objects.

```php
$rows = User::querySelect()
    ->where('true')
    ->ifQuery($timeFrom, 'AND created_ts >= ?', $timeFrom)
    ->ifQuery($timeTo, 'AND created_ts < ?', $timeTo)
    ->ifQuery($order && $orderDirection, 'ORDER BY #? !?', $order, $orderDirection)
    ->ifQuery($limit, 'LIMIT ?', $limit)
    ->ifQuery($offset, 'OFFSET ?', $offset)
    ->fetchAll();
```

Here is example of update request:

```php
User::queryUpdate()
    ->set('hash = ?', '[some old man]')
    ->where('created_ts < ?', dbtime('- 1 month'))
    ->exec();
```

Same for delete:

```php
User::queryDelete()
    ->where('created_ts < ?', dbtime('- 1 year'))
    ->exec();

// Lets inform user
printf("Deleted %d row(s)\n", User::query()->affectedRows());
```

Its easier to use `queryUpdateRow` method to update several fields:

```php
User::queryUpdateRow([
        'name' => '[some old man]',
        'hash' => md5('password'),
    ])
    ->where('created_ts < ?', dbtime('- 1 month'))
    ->exec();
```

In this case "SET" statement will be generated for passed fields with values escaped.

## Namespaces

By default model classes are created in root namespace. You can change it by
adding `"namespace"` option to your config:

```php
<?php

return [
    'host' => '192.168.99.100',
    'user' => 'test',
    'password' => 'test',
    'dbname' => 'test',
    'driver' => 'mysql',
    'namespace' => 'UserNamespace',
];
```

# Configuration profiles

You can create more then one profile to access different databases. In examples
above we used only one profile with name "default". To create more profiles you need
to define them in configuration like this:

```php
<?php

return [
    'default' => [
        'host' => '192.168.99.100',
        'user' => 'test',
        'password' => 'test',
        'dbname' => 'test',
        'driver' => 'mysql',
    ],
    'postgres' => [
        'host' => '192.168.99.100',
        'user' => 'test',
        'password' => 'test',
        'dbname' => 'test',
        'driver' => 'pgsql',
    ],
];
```

Now we have exactly same "default" profile as we had before and another one named "postgres".
Now we can use it by passing to our `db` function: 

```php
$rows = db('postgres')->q('SELECT * FROM users')->fetchAll();
```

Also we can pass profile to model generator, so the models will use it instead of
"default":

```php
$generator = \Dal\Model\GeneratorFactory::createGenerator('model', 'postgres');
```

# License

Copyright (c) 2009-2018 Nikolay Neizvesny

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

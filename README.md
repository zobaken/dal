# Introduction

Database Abstraction Layer for PHP.

Created to be simply as possible tool for creating and running SQL queries. Currently supports mysql and postgresql drivers.

# Requirements

PHP7, mysqli extension for Mysql support. pgsql extension for postgres..

# Installation

Install it via composer:

```
composer require zobaken/daltron dev-master
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
'config.php' will look like:

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

```php
\Dal\Dal::loadConfiguration('config.php');
```

# Query builder

Query generator mimics SQL syntax:

```php
$rows = db()
    ->select('*')
    ->from('test')
    ->where('created_ts = ?', $time)
    ->fetchAssoc();
```

Every "unknown" method of query, like `select` in this example adds term to
the SQL request. All parameters mapped with '?', that are not integer, 
are escaped and surrounded by quotes.
The previous example is equivalent to:

```php
$query = sprintf("SELECT * FROM test WHERE created_ts < '%s'",
    mysqli_real_escape_string($connection, $time)
);
$result = mysqli_query($connection, $query);
$rows = mysqli_fetch_assoc($result);
```

Here is a typical situation when query conditions depends on user input:

```php
$rows = db()->select('*')
    ->from('test')
    ->where('true')
    ->ifQuery($timeFrom, 'AND created_ts >= ?', $timeFrom)
    ->ifQuery($timeTo, 'AND created_ts < ?', $timeTo)
    ->ifQuery($order && $orderDirection, 'ORDER BY #? !?', $order, $orderDirection)
    ->ifQuery($limit, 'LIMIT ?', $limit)
    ->ifQuery($offset, 'OFFSET ?', $offset)
    ->fetchAllAssoc();
```

Additional conditions are added only when the first parameter of `ifQuery` is
true. '#?' placeholder is used for field name escaping. In this example we assume
that `$orderDirection` is 'ASC' or 'DESC' and '!?' placeholder does not escape the value,
use it with caution!

# License

Copyright (c) 2009-2018 Nikolay Neizvesny

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

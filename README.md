BTP API
=======

API library for [BTP daemon](https://github.com/mambaru/btp-daemon).

BTP daemon is performance analysis daemon from developers of [Mamba](http://corp.mamba.ru) portal.

This library is PHP 5.3 port of official old-style [library](https://github.com/mambaru/btp-api).

Example of usage
----------------

Let's say you have a code like this:

```php
function getSomethingFromDatabase()
{
    $data   = Database::getConnection()->query('SELECT * FROM `table` WHERE `id` IN (1, 2)');
    $result = array();

    foreach ($data as $row) {
        $result[] = $row[];
    }

    return $result;
}
```

First we should instantiate new Btp\Api\Connection object:

```php
use Btp\Api\Connection;

$btpConnection = new Connection();
```

Now we can work with counters.

There is two ways to use them:

* The explicit stop of counter.
```php
// Will be measured only time of Database::getConnection()->query()
function getSomethingFromDatabase(Connection $btpConnection)
{
    $counter = $btpConnection->getCounter(array(
        'srv'     => 'db7',
        'service' => 'mysql',
        'op'      => 'select',
    ));

    $data = Database::getConnection()->query('SELECT * FROM `table` WHERE `id` IN (1, 2)');
    $counter->stop();

    $result = array();

    foreach ($data as $row) {
        $result[] = $row[];
    }

    return $result;
}
```

* Counter stop in destructor.
```php
// Will be measured all operations from time of counter initialization till the function
// return statement (when the Btp\Api\Counter object's destructor will be called)
function getSomethingFromDatabase(Connection $btpConnection)
{
    $counter = $btpConnection->getCounter(array(
        'srv'     => 'db7',
        'service' => 'mysql',
        'op'      => 'select',
    ));

    $data   = Database::getConnection()->query('SELECT * FROM `table` WHERE `id` IN (1, 2)');
    $result = array();

    foreach ($data as $row) {
        $result[] = $row[];
    }

    return $result;
}
```
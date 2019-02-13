# Throttler
[![Latest Stable Version](https://img.shields.io/packagist/v/nimbly/Throttler.svg?style=flat-square)](https://packagist.org/packages/nimbly/throttler)
[![Build Status](https://img.shields.io/travis/nimbly/Throttler.svg?style=flat-square)](https://travis-ci.org/nimbly/Throttler)
[![License](https://img.shields.io/github/license/nimbly/Throttler.svg?style=flat-square)](https://packagist.org/packages/nimbly/throttler)


A framework agnostic request rate limiter.

## Description
Throttler can rate limit on any data point you like: IP address, user ID, API key, or any other uniquely identifying information you have access to.

## Installation

```bash
composer require nimbly/throttler
```

## Usage

### Storage adapter
You need a place to keep track of the hit counters - cache, database, or whatever. Create an instance of
a storage adapter to be passed into the Throttler. See the **Available storage adapters** section further down for complete list of adapters.

```php

$storageAdapter = new Throttler\Adapters\Redis(
    new Predis\Client('tcp://localhost:6379')
);

```

### Throttler

Instantiate Throttler by passing in a storage adapter instance.

```php

$throttler = new Throttler($storageAdapter);

```

## Constructor Options

You may pass in an array of key => value pair options as the second parameter of the constructor.

```php

$throttler = new Throttler($storageAdapter, ['key' => 'value']);

```

Supported options:

* **prefix** Prefix to apply to all keys and passed to the storage adapter. Defaults to **Throttler\\**.

## Methods

```hit(string $id, int $limit, int $decay) : boolean```

Log a hit on the throttler incrementing the rate limit counter. Returns **true** on success and **false** on failure.

* **id** is the unique ID of the source of this request. This value can be any string you'd like: IP address, a user ID, etc.
* **limit** is the total number of requests allowed over the timespan defined by **decay**.
* **decay** is the timespan allowed in **seconds**.

This example allows **120** requests in a **60** second timespan per **IP address**.

```php

if( $throttler->hit($request->ipAddress(), 120, 60) === false ){
    throw new TooManyRequestsHttpException(60, 'Slow it down man!');
}

```

---

```check(string $id) : int```

Check (but do not increment) the current rate limit counter for the given ID.


```php

if( $throttler->check($request->user->id) >= $warningThreshold ){
    $response = $response->withHeader("X-Rate-Limit", "Warning");
}

```

## Middleware
Add the throttler to your Middleware (you're using Middleware, right?)

```php

class ThrottleRequest implements SomeMiddlewareLibrary
{
    public function handle(Request $request, $next)
    {
        $storageAdapter = new Throttler\Adapters\Redis(
            new Predis\Client('tcp://localhost:6379')
        );

        $throttler = new Throttler($storageAdapter);

        if( $throttler->hit($request->ipAddress(), 120, 60) === false ){
            throw new TooManyRequestsHttpException(60, 'Slow it down man!');
        }

        return $next($request);
    }
}

```

## Available storage adapters
The following list of storage adapters are provided "out of the box":

### Redis
Requires the [Predis](https://github.com/nrk/predis) library available via [predis/predis](https://packagist.org/packages/predis/predis) on Packagist.

```php

$redisAdapter = new Throttler\Adapters\Redis(
    new Predis\Client("tcp://localhost:6379")
);

$throttler = new Throttler($redisAdapter);

```

### Database
The database adapter can use any PDO compatible database to persist throttler data. Just add this table to your database:

```sql

CREATE TABLE throttler
(
    key VARCHAR(64) PRIMARY KEY,
    hits INTEGER UNSIGNED NOT NULL DEFAULT 1,
    expires_at INTEGER UNSIGNED NOT NULL
)

```

```php

$databaseAdapter = new Throttler\Adapters\Database(
    new PDO("mysql:dbname=myapp;host=localhost", "username", "password")
);

$throttler = new Throttler($databaseAdapter);

```


You can also customize the columns that the Throttler will use along with garbage collection chance:

* **table** Table name to use. Defaults to **throttler**.
* **key** Key column name. Column type must be a string or varchar. Defaults to **key**.
* **hits** Hits column name. Column type must be an integer. Defaults to **hits**.
* **expires_at** Expiration column name. Column type must be an integer (UNIX timestamp). Defaults to **expires_at**.
* **gc_chance** Percent chance that garbage collection will run. A value less than 1 means it will **never** run. A value greater than 99 means it will run on **every** call. Defaults to **5**.

```php

$databaseAdapter = new Throttler\Adapters\Database(
    new PDO("mysql:dbname=myapp;host=localhost", "username", "password"),
    [
        "table" => "limiter",
        "key" => "id",
        "hits" => "value",
        "expires_at" => "ttl",
        "gc_chance" => 20,
    ]
);

$throttler = new Throttler($databaseAdapter);

```

### APCu
APCu is an in-memory PHP cache and requires the PECL APCu library available through most Linux package managers.

```php

$apcuAdapter = new Throttler\Adapters\Apcu;

$throttler = new Throttler($apcuAdapter);

```

### Memory
The memory adapter maintains its throttler purely in memory and does not persist its data between HTTP or CLI requets. This adapter is ideal for testing or other special use cases. Only use this adapter if you know what you are doing.

```php

$memoryAdapter = new Throttler\Adapters\Memory;

$throttler = new Throttler($memoryAdapter);

```


## Custom storage adapters
A ```Throttler\StorageAdapter``` interface is provided so that you may create your own adapters for any persistance engine you want. It must implement two methods:

```get(string $key) : int```

Returns the given key's current counter or 0 if key does not exist.

---

```increment(string $key, int $decay) : int```

Increments the counter for the given key. If key does not exist, it must create it and set its counter to 1 as well as set the counter to expire after **$decay** seconds. Returns the counter value.


```php

use Throttler\StorageAdapter;

class MyStorageAdapter implements StorageAdapter
{
    public function get(string $key): int
    {
        // Get $key from storage engine.
    }

    public function increment(string $key, int $decay): int
    {
        // Increment $key on storage engine and return new value.
    }
}

```
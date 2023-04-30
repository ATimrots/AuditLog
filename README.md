# AuditLog

## Requirements ##

* PHP 8.1
* [Composer](https://getcomposer.org) is required for installation

## Installation ##

Run the command below to install via Composer

```shell
composer require atimrots/audit-log
```

## Usage ##

After installing the library, you need to register the Provider class in your Laravel application. Open the `config/app.php` file and add the following line to the providers array

```php
Atimrots\AuditLog\Providers\AuditLogProvider::class
```
You need to provide some environment variables for the libraryto  be able to connect with API. You can add these variables to your `.env` file.
```
AUDIT_LOG_API_URL=
AUDIT_LOG_API_USERNAME=
AUDIT_LOG_API_PASSWORD=
AUDIT_LOG_API_SESSION_TIME=3600
```
*AUDIT_LOG_API_SESSION_TIME, This value means how long (time in seconds) the access token will be kept in the Laravel cache. By default, the token will be valid as long as the configured [MongoLogsApi](https://github.com/ATimrots/mongo-logs-api). A new token will be automatically requested when the beam time is up.

Here ir some examples how you can use this library.

You can post log from any place in code using class `Atimrots\AuditLog\AuditLog`. You need to call the method `post`, providing two parameters `string $collection` and `array $data`. Collection is one of predefined logs collections and data is body of your specific log.
Note that each collection has a predefined schema and the submitted data will be validated against it.
```php
use Atimrots\AuditLog\AuditLog;

AuditLog::post('price_changes', [
  'project' => 'ExampleProject',
  'product_uuid' => '709cfcfb-3553-43ed-81d1-e014955dda10',
  'old' => 10.45,
  'new' => 83.12,
  'price_type' => 'cost_price',
  'time' => Carbon::now()->format('Y-m-d\TH:i:sO')
]);
```
To retrive your posted logs You need to call method `get` on the same class.
Provide fallowing parameters:
1. `string $collection` same as posting;
2. `array $query`, where you can set `page` & `limit` attributes to limit selected result. If you leave it empty, by default, value for page will be 1 & limit 2000.
3. `array $body` is request body and contains query parametrs to execute find operation. If you leave it in mongo query it is equal to `db.collection.find({})` and will return list of latest stored logs.

This example will return first 50 results from "price_changes" collection where `product_uuid` is '435089ef-297a-45ee-8327-81ef319116d1' and log creation time is greater than 2023-04-10T15:53:00+04:00.
```php
$q = [
  'product_uuid' => '435089ef-297a-45ee-8327-81ef319116d1',
  'time' => ['$gt' => '2023-04-10T15:53:00+04:00'],
];

$response = AuditLog::get('price_changes', ['page' => 1, 'limit' => 50], $q);
```
Returned result of executed example request:
```php
[
   'exception' => null,
   'response' => [
      'result' => [
         [
            '_id' => [
               '$oid':'643effc4387f8bf196a1f6d1'
            ],
            'project' => 'ExampleProject',
            'product_uuid' => '435089ef-297a-45ee-8327-81ef319116d1',
            'price_type' => 'cost_price',
            'old' => 68.4,
            'new' => 100.45,
            'time' => '2023-04-11T15:53:00+05:00'
         ],
        ...
      ],
      'page' => 1,
      'limit' => 50,
      'total' => 4
   ],
   'original_exception' => null
]
```

# Cache
A simple caching engine which is highly customizable. This library can be used to implement caching at 3 areas, application, server and database. The cached items are Time-Based, meaning that each item will have a Time-to-Live. Once passed, item will be considered as expired.

<p align="center">
  <a href="https://github.com/WebFiori/cache/actions"><img src="https://github.com/WebFiori/cache/actions/workflows/php84.yml/badge.svg?branch=main"></a>
  <a href="https://codecov.io/gh/WebFiori/cache">
    <img src="https://codecov.io/gh/WebFiori/cache/branch/main/graph/badge.svg" />
  </a>
  <a href="https://sonarcloud.io/dashboard?id=WebFiori_cache">
      <img src="https://sonarcloud.io/api/project_badges/measure?project=WebFiori_cache&metric=alert_status" />
  </a>
  <a href="https://github.com/WebFiori/cache/releases">
      <img src="https://img.shields.io/github/release/WebFiori/cache.svg?label=latest" />
  </a>
  <a href="https://packagist.org/packages/webfiori/cache">
      <img src="https://img.shields.io/packagist/dt/webfiori/cache?color=light-green">
  </a>
</p>


## Content
- [Installation](#installation)
- [Supported PHP Versions](#supported-php-versions)
- [Usage](usage)
  - [Creating Cache Item](#creating-cache-item)
  - [Retrieving Items](#retrieving-items)
    - [Retrieve Only](#retrieve-only)
    - [Retrieve or Create](#retrieve-or-create)
  - [Other Operations](#other-operations)
    - [Determining Item Existence](#determining-item-existence)
    - [Removing an Item](#removing-an-item)
    - [Flush the Cache](#flush-the-cache)
    - [Enabling or Disabling Cache](#enabling-or-disabling-cache)
    - [Set Custom Cache Store](#set-custom-cache-store)
- [License](#license)

## Installation
If you are using composer to manage your dependencies, then it is possible to install the library by including the entry `"webfiori/cache":"*"` in the `require` section of your `composer.json` file to install the latest release.

Another way to include the library is by going to releases and download the latest release and extract compressed file content and add them to your include directory.

## Supported PHP Versions
|                                                                                        Build Status                                                                                        |
|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php70.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php70.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php71.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php71.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php72.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php72.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php73.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php73.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php74.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php74.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php80.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php80.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php81.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php81.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php82.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php82.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php83.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php83.yml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php84.yml"><img src="https://github.com/WebFiori/cache/actions/workflows/php84.yml/badge.svg?branch=main"></a>  |
## Usage
The normal workflow of using the library is as follows:
* Cache items are created.
* Cache items are accessed from cache as needed.
* Cached items are re-validated with every access.
* If expired, items are removed from the cache or re-created.

### Creating Cache Item
Creating cache entries is performed using the method `Cache::set($key, $data)`. The method accepts two mandatory parameters, first one is the key of cache item and second argument is the data that will be cached. Data can be of any type.

```php
Cache::set('my_item', 'Any Data');
```

This would create a cache entry with duration of 60 seconds. This means if a user tries to access same item after 60 seconds, cache will miss.

To customize time-to-live, a third parameter can be passed which represents the time at which the item will be kept in cache.

```php
Cache::set('my_item', 'Any Data', 3600); //Keep the item for one hour in the cache.
```

To override and revalidate specific cache item, a fourth boolean argument can be used to achieve that.

```php
Cache::set('my_item', 'Any Data', 3600, true);
```

### Retrieving Items
There are two approaches to get cache items, one is retrieve only and the second one is retrieve or create.

#### Retrieve Only
This approach will attempt to search for the item in cache and return it if found. If not found, null is returned.

```php
$data = Cache::get('my_item');
```

#### Retrieve or Create
This approach is recommended as it will initially attempt to retrieve the item from the cache. If not found, it will create it using a custom callback.

```php
$data = Cache::get('my_item', function () {
 return 'This is a test.';
}, 3600);
```

The callback accepts passing parameters as an array as shown in the next sample.

```php
$data = Cache::get('my_item', function ($p1, $p2) {
 return 'This is a test. '.$p1.' '.$p2;
 //Output: 'This is a test. Hello World'
}, 3600, ['Hello', 'World']);
```

### Other Operations

#### Determining Item Existence
```php
Cache::has('item_key');
```

#### Removing an Item
```php
Cache::delete('item_key');
```

#### Flush the Cache
```php
Cache::flush();
```

#### Enabling or Disabling Cache
Enabling cache.
```php
Cache::setEnabled(true);
```

Disabling cache.
```php
Cache::setEnabled(false);
```

#### Set Custom Cache Store
Developer can create his own custom cache store. To achieve this, the interface `webfiori\cache\Store` must be implemented.

```php
$driver = new MyCustomDriver();
Cache::setDriver($driver);
```

## License
This library is licensed under MIT license.

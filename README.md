# WebFiori Cache

A simple, secure, and highly customizable caching engine for PHP. This library provides time-based caching with built-in encryption support, making it suitable for application-level, server-level, and database-level caching scenarios.

<p align="center">
  <a href="https://github.com/WebFiori/cache/actions"><img src="https://github.com/WebFiori/cache/actions/workflows/php84.yaml/badge.svg?branch=main"></a>
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

## Features

- **Instance-based API** — fully DI-friendly, multiple cache pools can coexist
- **Static facade** (`CacheFacade`) for quick usage without DI
- **Time-based caching** with configurable TTL (Time-to-Live)
- **Built-in encryption** for sensitive data protection
- **Multiple storage backends** (File-based included, extensible via `Storage` interface)
- **Key prefixing** for namespace isolation (`withPrefix()` returns a new instance — no mutation)
- **Expired item cleanup** via `purgeExpired()`
- **Comprehensive error handling** with custom exceptions

## Installation

```bash
composer require webfiori/cache
```

## Supported PHP Versions

This library requires **PHP 8.1 or higher**.

|                                                                                        Build Status                                                                                        |
|:------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------:|
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php81.yaml"><img src="https://github.com/WebFiori/cache/actions/workflows/php81.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php82.yaml"><img src="https://github.com/WebFiori/cache/actions/workflows/php82.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php83.yaml"><img src="https://github.com/WebFiori/cache/actions/workflows/php83.yaml/badge.svg?branch=main"></a>  |
| <a target="_blank" href="https://github.com/WebFiori/cache/actions/workflows/php84.yaml"><img src="https://github.com/WebFiori/cache/actions/workflows/php84.yaml/badge.svg?branch=main"></a>  |

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

// Set up encryption key (recommended for production)
$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Create a cache instance
$cache = new Cache(new FileStorage('/path/to/cache'));

// Store and retrieve
$cache->set('greeting', 'Hello, World!', 3600);
echo $cache->get('greeting'); // Hello, World!

// Auto-populate on cache miss using a generator callback
$data = $cache->get('user_data', function () {
    return fetchUserDataFromDatabase();
}, 3600);

// Check, delete, flush
$cache->has('greeting');   // true
$cache->delete('greeting');
$cache->flush();
```

> For a complete runnable version of this, see the [Set and Get](examples/basic/01-set-and-get/) example.

## Usage

### Basic Operations

```php
use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;

$cache = new Cache(new FileStorage('/path/to/cache'));

// Store with default TTL (60 seconds)
$cache->set('key', 'value');

// Store with custom TTL and override existing
$cache->set('key', 'new_value', 3600, true);

// Retrieve (returns null on miss)
$data = $cache->get('key');

// Generator with parameters
$profile = $cache->get('user_profile', function ($userId, $includePrefs) {
    return fetchUserProfile($userId, $includePrefs);
}, 3600, [123, true]);

// Update TTL of existing item
$cache->setTTL('key', 7200);

// Enable/disable caching (useful for debugging)
$cache->setEnabled(false);
```

> Working examples for each operation: [basic examples](examples/basic/)

### Prefix Isolation

`withPrefix()` returns a new `Cache` instance — the original is never mutated.

```php
$users = $cache->withPrefix('users_');
$orders = $cache->withPrefix('orders_');

$users->set('count', 100, 60);
$orders->set('count', 250, 60);

$users->get('count');  // 100
$orders->get('count'); // 250

// Flush only one prefix
$users->flush();
```

> See [Prefix Isolation](examples/advanced/01-prefix-isolation/) and [Prefix Flush](examples/advanced/02-prefix-flush/).

### Static Facade

For quick usage without dependency injection, use `CacheFacade`:

```php
use WebFiori\Cache\CacheFacade;

CacheFacade::set('key', 'value', 60);
echo CacheFacade::get('key');

// withPrefix() returns a Cache instance
CacheFacade::withPrefix('users_')->set('count', 100, 60);
```

### Security & Encryption

All cached data is encrypted by default using AES-256-CBC. Configure via environment variables or programmatically:

```bash
# 64-character hexadecimal encryption key (required for encryption)
CACHE_ENCRYPTION_KEY=your64characterhexadecimalencryptionkeyhere1234567890abcdef

# Optional settings
CACHE_ENCRYPTION_ENABLED=true
CACHE_ENCRYPTION_ALGORITHM=aes-256-cbc
CACHE_FILE_PERMISSIONS=600
CACHE_DIR_PERMISSIONS=700
```

```php
use WebFiori\Cache\KeyManager;

// Generate and set a key programmatically
$key = KeyManager::generateKey();
KeyManager::setEncryptionKey($key);
```

> See [Encryption Key Management](examples/advanced/03-encryption-key-management/), [Security Config](examples/advanced/04-security-config/), and [Encrypt/Decrypt Round-Trip](examples/advanced/06-encrypt-decrypt-roundtrip/).

### Custom Storage Drivers

Implement the `Storage` interface to create custom backends (e.g., Redis, Memcached, database):

```php
use WebFiori\Cache\Storage;
use WebFiori\Cache\Item;

class MemoryStorage implements Storage {
    public function store(Item $item) { /* ... */ }
    public function read(string $key, ?string $prefix) { /* ... */ }
    public function readItem(string $key, ?string $prefix): ?Item { /* ... */ }
    public function has(string $key, ?string $prefix): bool { /* ... */ }
    public function delete(string $key) { /* ... */ }
    public function flush(?string $prefix) { /* ... */ }
    public function purgeExpired(): int { /* ... */ }
}

$cache = new Cache(new MemoryStorage());
```

> Full working implementation: [Custom Storage Driver](examples/advanced/07-custom-storage-driver/).

## API Reference

### Cache Class (Instance Methods)

| Method | Description | Returns |
|--------|-------------|---------|
| `get($key, $generator, $ttl, $params)` | Retrieve or create cache item | `mixed` |
| `set($key, $data, $ttl, $override)` | Store cache item | `bool` |
| `has($key)` | Check if item exists and is not expired | `bool` |
| `delete($key)` | Remove cache item | `void` |
| `flush()` | Clear all cache (respects current prefix) | `void` |
| `getItem($key)` | Get `Item` object with full metadata | `Item\|null` |
| `setTTL($key, $ttl)` | Update item TTL | `bool` |
| `isEnabled()` / `setEnabled($bool)` | Check or toggle caching | `bool` / `void` |
| `setDriver($driver)` / `getDriver()` | Set or get storage driver | `void` / `Storage` |
| `withPrefix($prefix)` | Returns new `Cache` instance with prefix | `Cache` |
| `getPrefix()` | Get current prefix | `string` |
| `purgeExpired()` | Remove all expired items from storage | `int` |

### CacheFacade Class (Static Methods)

Same methods as `Cache`, plus:

| Method | Description | Returns |
|--------|-------------|---------|
| `getInstance()` | Get the default `Cache` instance | `Cache` |
| `setInstance($cache)` | Replace the default instance | `void` |
| `reset()` | Destroy the default instance (for testing) | `void` |

### Item Class

| Method | Description | Returns |
|--------|-------------|---------|
| `getKey()` | Get item key | `string` |
| `getData()` / `getDataDecrypted()` / `getDataEncrypted()` | Get raw, decrypted, or encrypted data | `mixed` / `mixed` / `string` |
| `getTTL()` / `setTTL($ttl)` | Get or set time-to-live | `int` / `void` |
| `getCreatedAt()` / `getExpiryTime()` | Get creation or expiry timestamp | `int` |
| `getSecurityConfig()` / `setSecurityConfig($config)` | Get or set security configuration | `SecurityConfig` / `void` |
| `generateKey()` | Generate encryption key (static) | `string` |

### Storage Interface

| Method | Description |
|--------|-------------|
| `store(Item $item)` | Store cache item |
| `read(string $key, ?string $prefix)` | Read item data |
| `readItem(string $key, ?string $prefix)` | Read item as `Item` object |
| `has(string $key, ?string $prefix)` | Check item existence |
| `delete(string $key)` | Delete item |
| `flush(?string $prefix)` | Clear cache |
| `purgeExpired()` | Remove expired items, return count removed |

## Error Handling

| Exception | When |
|-----------|------|
| `InvalidCacheKeyException` | Empty key, key > 250 chars, key with control characters |
| `CacheStorageException` | File system errors, invalid paths, permission issues |
| `CacheDriverException` | Invalid driver implementation |
| `CacheException` | Base exception, missing encryption key |

> See [Error Handling](examples/advanced/10-error-handling/) for all cases.

## Upgrading from v2

v3 changes `Cache` from a static singleton to an instance class. Migration is straightforward:

```php
// v2 (static singleton)                    // v3 (instance-based)
Cache::set('k', 'v', 60);                  $cache = new Cache(new FileStorage($path));
Cache::get('k');                            $cache->set('k', 'v', 60);
                                            $cache->get('k');

Cache::withPrefix('x')->get('k');           $cache->withPrefix('x')->get('k');
// ⚠️ prefix leaked to all future calls    // ✅ prefix is scoped, original unchanged

Cache::setDriver($driver);                  $cache = new Cache($driver);

Cache::create($driver, true, 'pfx');        new Cache($driver, true, 'pfx');
```

If you prefer the static API, use `CacheFacade` — it works identically to v2's `Cache`:

```php
use WebFiori\Cache\CacheFacade;

CacheFacade::set('k', 'v', 60);
CacheFacade::get('k');
```

## Examples

The [examples/](examples/) directory contains 23 self-contained, runnable samples organized by difficulty:

**Basic** — Core operations every user needs:

| # | Example | What it demonstrates |
|---|---------|----------------------|
| 01 | [Set and Get](examples/basic/01-set-and-get/) | `set()` and `get()` |
| 02 | [Get with Generator](examples/basic/02-get-with-generator/) | Auto-populate on cache miss |
| 03 | [Generator with Params](examples/basic/03-generator-with-params/) | Pass arguments to the callback |
| 04 | [Check Existence](examples/basic/04-check-existence/) | `has()` |
| 05 | [Delete Item](examples/basic/05-delete-item/) | `delete()` |
| 06 | [Flush Cache](examples/basic/06-flush-cache/) | `flush()` |
| 07 | [Update TTL](examples/basic/07-update-ttl/) | `setTTL()` |
| 08 | [Override Behavior](examples/basic/08-override-behavior/) | `set()` with/without override |
| 09 | [TTL Expiration](examples/basic/09-ttl-expiration/) | Items expire after TTL |
| 10 | [Enable / Disable](examples/basic/10-enable-disable/) | Toggle caching on/off |
| 11 | [Item Inspection](examples/basic/11-item-inspection/) | Read item metadata |
| 12 | [Data Types](examples/basic/12-data-types/) | Strings, arrays, objects, edge cases |

**Advanced** — Security, extensibility, and error handling:

| # | Example | What it demonstrates |
|---|---------|----------------------|
| 01 | [Prefix Isolation](examples/advanced/01-prefix-isolation/) | Namespace isolation |
| 02 | [Prefix Flush](examples/advanced/02-prefix-flush/) | Selective flush by prefix |
| 03 | [Encryption Key Management](examples/advanced/03-encryption-key-management/) | `KeyManager` usage |
| 04 | [Security Config](examples/advanced/04-security-config/) | Algorithm and permissions |
| 05 | [Encryption Disabled](examples/advanced/05-encryption-disabled/) | Store without encryption |
| 06 | [Encrypt/Decrypt Round-Trip](examples/advanced/06-encrypt-decrypt-roundtrip/) | Full encryption cycle |
| 07 | [Custom Storage Driver](examples/advanced/07-custom-storage-driver/) | Implement `Storage` interface |
| 08 | [File Storage Custom Path](examples/advanced/08-file-storage-custom-path/) | Custom cache directory |
| 09 | [File Permissions](examples/advanced/09-file-permissions/) | Default and custom permissions |
| 10 | [Error Handling](examples/advanced/10-error-handling/) | All exception types |
| 11 | [Factory and DI](examples/advanced/11-factory-and-di/) | Constructor-based DI |

Run any example:

```bash
composer install
php examples/basic/01-set-and-get/index.php
```

## License

This library is licensed under MIT License. See [LICENSE](LICENSE) file for more details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Run the test suite: `composer test`
6. Submit a pull request

## Support

- **Issues**: [GitHub Issues](https://github.com/WebFiori/cache/issues)
- **Documentation**: This README and [examples/](examples/)

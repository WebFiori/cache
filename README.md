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

- **Time-based caching** with configurable TTL (Time-to-Live)
- **Built-in encryption** for sensitive data protection
- **Multiple storage backends** (File-based included, extensible via Storage interface)
- **Static API** for backward compatibility and simple usage
- **Key prefixing** for namespace isolation
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
use WebFiori\Cache\KeyManager;

// Set up encryption key (recommended for production)
$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store and retrieve
Cache::set('greeting', 'Hello, World!', 3600);
echo Cache::get('greeting'); // Hello, World!

// Auto-populate on cache miss using a generator callback
$data = Cache::get('user_data', function () {
    return fetchUserDataFromDatabase();
}, 3600);

// Check, delete, flush
Cache::has('greeting');   // true
Cache::delete('greeting');
Cache::flush();
```

> For a complete runnable version of this, see [examples/basic/01-set-and-get](examples/basic/01-set-and-get/).

## Usage

### Basic Operations

```php
// Store with default TTL (60 seconds)
Cache::set('key', 'value');

// Store with custom TTL and override existing
Cache::set('key', 'new_value', 3600, true);

// Retrieve (returns null on miss)
$data = Cache::get('key');

// Generator with parameters
$profile = Cache::get('user_profile', function ($userId, $includePrefs) {
    return fetchUserProfile($userId, $includePrefs);
}, 3600, [123, true]);

// Update TTL of existing item
Cache::setTTL('key', 7200);

// Enable/disable caching (useful for debugging)
Cache::setEnabled(false);
```

> Working examples for each operation: [examples/basic/](examples/basic/)

### Prefix Isolation

```php
Cache::withPrefix('users_')->set('count', 100, 60);
Cache::withPrefix('orders_')->set('count', 250, 60);

// Each prefix is an isolated namespace
Cache::withPrefix('users_')->get('count');  // 100
Cache::withPrefix('orders_')->get('count'); // 250

// Flush only one prefix
Cache::withPrefix('users_')->flush();
```

> See [examples/advanced/01-prefix-isolation](examples/advanced/01-prefix-isolation/) and [examples/advanced/02-prefix-flush](examples/advanced/02-prefix-flush/).

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

> See [examples/advanced/03-encryption-key-management](examples/advanced/03-encryption-key-management/), [examples/advanced/04-security-config](examples/advanced/04-security-config/), and [examples/advanced/06-encrypt-decrypt-roundtrip](examples/advanced/06-encrypt-decrypt-roundtrip/).

### Custom Storage Drivers

Implement the `Storage` interface to create custom backends (e.g., Redis, Memcached, database):

```php
use WebFiori\Cache\Storage;
use WebFiori\Cache\Item;

class MemoryStorage implements Storage {
    private array $store = [];

    public function store(Item $item) { /* ... */ }
    public function read(string $key, ?string $prefix) { /* ... */ }
    public function readItem(string $key, ?string $prefix): ?Item { /* ... */ }
    public function has(string $key, ?string $prefix): bool { /* ... */ }
    public function delete(string $key) { /* ... */ }
    public function flush(?string $prefix) { /* ... */ }
}

Cache::setDriver(new MemoryStorage());
```

> Full working implementation: [examples/advanced/07-custom-storage-driver](examples/advanced/07-custom-storage-driver/).

## API Reference

### Cache Class (Static Methods)

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
| `withPrefix($prefix)` | Set key prefix for namespace isolation | `Cache` |
| `create($driver, $enabled, $prefix)` | Factory method for DI | `Cache` |

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

## Error Handling

The library provides specific exceptions for different error conditions:

| Exception | When |
|-----------|------|
| `InvalidCacheKeyException` | Empty key, key > 250 chars, key with control characters |
| `CacheStorageException` | File system errors, invalid paths, permission issues |
| `CacheDriverException` | Invalid driver implementation |
| `CacheException` | Base exception, missing encryption key |

> See [examples/advanced/10-error-handling](examples/advanced/10-error-handling/) for all cases.

## Examples

The [examples/](examples/) directory contains 23 self-contained, runnable samples organized by difficulty:

**Basic** — Core operations every user needs:

| # | Example | What it demonstrates |
|---|---------|----------------------|
| 01 | [Set and Get](examples/basic/01-set-and-get/) | `Cache::set()` and `Cache::get()` |
| 02 | [Get with Generator](examples/basic/02-get-with-generator/) | Auto-populate on cache miss |
| 03 | [Generator with Params](examples/basic/03-generator-with-params/) | Pass arguments to the callback |
| 04 | [Check Existence](examples/basic/04-check-existence/) | `Cache::has()` |
| 05 | [Delete Item](examples/basic/05-delete-item/) | `Cache::delete()` |
| 06 | [Flush Cache](examples/basic/06-flush-cache/) | `Cache::flush()` |
| 07 | [Update TTL](examples/basic/07-update-ttl/) | `Cache::setTTL()` |
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
| 11 | [Factory and DI](examples/advanced/11-factory-and-di/) | `Cache::create()` for DI |

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

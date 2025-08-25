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

## Table of Contents

- [Installation](#installation)
- [Supported PHP Versions](#supported-php-versions)
- [Quick Start](#quick-start)
- [Usage](#usage)
  - [Basic Operations](#basic-operations)
  - [Advanced Usage](#advanced-usage)
  - [Dependency Injection](#dependency-injection)
  - [Security Configuration](#security-configuration)
  - [Custom Storage Drivers](#custom-storage-drivers)
- [API Reference](#api-reference)
- [Configuration](#configuration)
- [Security](#security)
- [License](#license)

## Installation

### Using Composer

```bash
composer require webfiori/cache
```

### Manual Installation

1. Download the latest release from [GitHub releases](https://github.com/WebFiori/cache/releases)
2. Extract the files to your project directory
3. Include the library in your project:

```php
require_once 'path/to/WebFiori/Cache/Cache.php';
// You'll also need to manually include all dependencies
require_once 'path/to/WebFiori/Cache/Storage.php';
require_once 'path/to/WebFiori/Cache/Item.php';
require_once 'path/to/WebFiori/Cache/FileStorage.php';
require_once 'path/to/WebFiori/Cache/KeyManager.php';
require_once 'path/to/WebFiori/Cache/SecurityConfig.php';
// And all exception classes...
```

**Note:** Manual installation is not recommended. Use Composer for automatic dependency management.

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

// Set up encryption key (recommended for production)
$_ENV['CACHE_ENCRYPTION_KEY'] = 'your-64-character-hex-key-here';

// Simple cache usage
$data = Cache::get('user_data', function() {
    // This callback runs only on cache miss
    return fetchUserDataFromDatabase();
}, 3600); // Cache for 1 hour

// Check if item exists
if (Cache::has('user_data')) {
    echo "Data is cached!";
}

// Remove specific item
Cache::delete('user_data');

// Clear all cache
Cache::flush();
```

## Usage

### Basic Operations

#### Creating Cache Items

```php
use WebFiori\Cache\Cache;

// Store data with default TTL (60 seconds)
Cache::set('my_key', 'Hello World');

// Store data with custom TTL (1 hour)
Cache::set('my_key', 'Hello World', 3600);

// Override existing cache item
Cache::set('my_key', 'New Value', 3600, true);
```

#### Retrieving Items

**Retrieve Only:**
```php
// Returns cached data or null if not found/expired
$data = Cache::get('my_key');

if ($data === null) {
    echo "Cache miss!";
}
```

**Retrieve or Create:**
```php
// Recommended approach - automatically handles cache miss
$data = Cache::get('expensive_operation', function() {
    // This callback only runs on cache miss
    return performExpensiveOperation();
}, 1800); // Cache for 30 minutes

// With parameters
$userData = Cache::get('user_profile', function($userId, $includePrefs) {
    return fetchUserProfile($userId, $includePrefs);
}, 3600, [123, true]); // Pass parameters to callback
```

#### Other Operations

**Check Item Existence:**
```php
if (Cache::has('my_key')) {
    echo "Item exists and is not expired";
}
```

**Remove Single Item:**
```php
Cache::delete('my_key');
```

**Clear All Cache:**
```php
Cache::flush();
```

**Update TTL:**
```php
// Extend TTL of existing item to 2 hours
Cache::setTTL('my_key', 7200);
```

**Enable/Disable Caching:**
```php
// Disable caching (useful for debugging)
Cache::setEnabled(false);

// Re-enable caching
Cache::setEnabled(true);

// Check if caching is enabled
if (Cache::isEnabled()) {
    echo "Caching is active";
}
```

### Advanced Usage

#### Working with Cache Items

```php
use WebFiori\Cache\Cache;
use WebFiori\Cache\Item;

// Get detailed cache item information
$item = Cache::getItem('my_key');

if ($item !== null) {
    echo "Key: " . $item->getKey() . "\n";
    echo "Created: " . date('Y-m-d H:i:s', $item->getCreatedAt()) . "\n";
    echo "Expires: " . date('Y-m-d H:i:s', $item->getExpiryTime()) . "\n";
    echo "TTL: " . $item->getTTL() . " seconds\n";
    echo "Data: " . $item->getData() . "\n";
}
```

#### Key Prefixing

```php
// Set a prefix for cache isolation
Cache::withPrefix('user_');

// This will actually store with key 'user_profile_123'
Cache::set('profile_123', $userData);

// Or chain the prefix setting
Cache::withPrefix('session_')->set('abc123', $sessionData);
```

### Dependency Injection

For modern applications, use dependency injection instead of static methods:

```php
use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;

// Create cache instance with custom storage
$storage = new FileStorage('/path/to/cache/directory');
$cache = new Cache($storage, true, 'myapp_');

// Note: The Cache class currently only supports static methods
// For dependency injection, you would need to create a wrapper class
// or use the static methods with custom drivers:

// Set custom driver globally
Cache::setDriver($storage);

// Then use static methods as normal
Cache::set('key', 'value', 3600);
$data = Cache::get('key');

// Or create a factory method
$cache = Cache::create($storage, true, 'myapp_');
```

### Security Configuration

#### Environment Variables

Set these environment variables for security configuration:

```bash
# Required: 64-character hexadecimal encryption key
CACHE_ENCRYPTION_KEY=your64characterhexadecimalencryptionkeyhere1234567890abcdef

# Optional: Security settings
CACHE_ENCRYPTION_ENABLED=true
CACHE_ENCRYPTION_ALGORITHM=aes-256-cbc
CACHE_FILE_PERMISSIONS=600
CACHE_DIR_PERMISSIONS=700
```

#### Programmatic Configuration

```php
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;
use WebFiori\Cache\Item;

// Generate and set encryption key
$key = KeyManager::generateKey();
KeyManager::setEncryptionKey($key);

// Custom security configuration
$config = new SecurityConfig();
$config->setEncryptionEnabled(true);
$config->setEncryptionAlgorithm('aes-256-gcm');
$config->setFilePermissions(0600);
$config->setDirectoryPermissions(0700);

// Apply to cache item
$item = new Item('secure_data', $sensitiveData, 3600);
$item->setSecurityConfig($config);
```

### Custom Storage Drivers

Implement the `Storage` interface to create custom storage backends:

```php
use WebFiori\Cache\Storage;
use WebFiori\Cache\Item;

class RedisStorage implements Storage {
    private $redis;
    
    public function __construct($redisConnection) {
        $this->redis = $redisConnection;
    }
    
    public function store(Item $item) {
        $key = $item->getPrefix() . $item->getKey();
        $data = $item->getDataEncrypted();
        $this->redis->setex($key, $item->getTTL(), $data);
    }
    
    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);
        return $item ? $item->getDataDecrypted() : null;
    }
    
    public function readItem(string $key, ?string $prefix): ?Item {
        $fullKey = $prefix . $key;
        $data = $this->redis->get($fullKey);
        
        if ($data === false) {
            return null;
        }
        
        // Reconstruct Item object from stored data
        // Implementation depends on your storage format
        return $this->reconstructItem($key, $data);
    }
    
    public function has(string $key, ?string $prefix): bool {
        return $this->redis->exists($prefix . $key);
    }
    
    public function delete(string $key) {
        $this->redis->del($key);
    }
    
    public function flush(?string $prefix) {
        if ($prefix) {
            $keys = $this->redis->keys($prefix . '*');
            if (!empty($keys)) {
                $this->redis->del($keys);
            }
        } else {
            $this->redis->flushdb();
        }
    }
    
    private function reconstructItem(string $key, $data): Item {
        // Implementation depends on your data format
        // This is a simplified example
        return new Item($key, $data, 3600);
    }
}

// Use custom storage
$redisStorage = new RedisStorage($redisConnection);
Cache::setDriver($redisStorage);
```

## API Reference

### Cache Class (Static Methods)

| Method | Description | Parameters | Returns |
|--------|-------------|------------|---------|
| `get($key, $generator, $ttl, $params)` | Retrieve or create cache item | `string $key`, `callable $generator`, `int $ttl = 60`, `array $params = []` | `mixed` |
| `set($key, $data, $ttl, $override)` | Store cache item | `string $key`, `mixed $data`, `int $ttl = 60`, `bool $override = false` | `bool` |
| `has($key)` | Check if item exists | `string $key` | `bool` |
| `delete($key)` | Remove cache item | `string $key` | `void` |
| `flush()` | Clear all cache | - | `void` |
| `getItem($key)` | Get detailed item info | `string $key` | `Item\|null` |
| `setTTL($key, $ttl)` | Update item TTL | `string $key`, `int $ttl` | `bool` |
| `isEnabled()` | Check if caching is enabled | - | `bool` |
| `setEnabled($enable)` | Enable/disable caching | `bool $enable` | `void` |
| `setDriver($driver)` | Set storage driver | `Storage $driver` | `void` |
| `getDriver()` | Get current storage driver | - | `Storage` |
| `withPrefix($prefix)` | Set key prefix | `string $prefix` | `Cache` |

### Item Class

| Method | Description | Returns |
|--------|-------------|---------|
| `getKey()` | Get item key | `string` |
| `getData()` | Get raw data | `mixed` |
| `getDataDecrypted()` | Get decrypted data | `mixed` |
| `getDataEncrypted()` | Get encrypted data | `string` |
| `getTTL()` | Get time-to-live | `int` |
| `getCreatedAt()` | Get creation timestamp | `int` |
| `getExpiryTime()` | Get expiry timestamp | `int` |
| `setTTL($ttl)` | Set time-to-live | `void` |
| `generateKey()` | Generate encryption key (static) | `string` |

### Storage Interface

| Method | Description | Parameters |
|--------|-------------|------------|
| `store($item)` | Store cache item | `Item $item` |
| `read($key, $prefix)` | Read item data | `string $key`, `?string $prefix` |
| `readItem($key, $prefix)` | Read item object | `string $key`, `?string $prefix` |
| `has($key, $prefix)` | Check item existence | `string $key`, `?string $prefix` |
| `delete($key)` | Delete item | `string $key` |
| `flush($prefix)` | Clear cache | `?string $prefix` |

## Configuration

### Environment Variables

| Variable | Description | Default | Example |
|----------|-------------|---------|---------|
| `CACHE_ENCRYPTION_KEY` | 64-char hex encryption key | Required | `a1b2c3d4e5f6...` |
| `CACHE_ENCRYPTION_ENABLED` | Enable/disable encryption | `true` | `true\|false` |
| `CACHE_ENCRYPTION_ALGORITHM` | Encryption algorithm | `aes-256-cbc` | `aes-256-gcm` |
| `CACHE_FILE_PERMISSIONS` | Cache file permissions | `600` | `644` |
| `CACHE_DIR_PERMISSIONS` | Cache directory permissions | `700` | `755` |

### File Storage Configuration

The default `FileStorage` class stores cache files in the `WebFiori/Cache/cache` directory. You can customize this:

```php
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\Cache;

// Custom cache directory
$storage = new FileStorage('/var/cache/myapp');
Cache::setDriver($storage);
```

## Security

### Encryption

- All cached data is encrypted by default using AES-256-CBC
- Encryption keys should be 64-character hexadecimal strings
- Keys are managed through environment variables or `KeyManager` class
- Each cache item can have its own encryption configuration

### File Permissions

- Cache files are created with restrictive permissions (600 by default)
- Cache directories use 700 permissions by default
- Permissions are configurable via environment variables or `SecurityConfig`

### Best Practices

1. **Always set an encryption key** in production environments
2. **Use environment variables** for sensitive configuration
3. **Regularly rotate encryption keys** for high-security applications
4. **Set appropriate file permissions** for your environment
5. **Use prefixes** to isolate different application components
6. **Implement proper error handling** for cache operations

### Key Generation

```php
use WebFiori\Cache\KeyManager;

// Generate a new encryption key
$key = KeyManager::generateKey();
echo "Your new encryption key: " . $key;

// Save this key securely and set it as CACHE_ENCRYPTION_KEY
```

## Error Handling

The library provides specific exceptions for different error conditions:

```php
use WebFiori\Cache\Cache;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;
use WebFiori\Cache\Exceptions\CacheStorageException;
use WebFiori\Cache\Exceptions\CacheDriverException;
use WebFiori\Cache\Exceptions\CacheException;

try {
    Cache::set('', 'data'); // Invalid key
} catch (InvalidCacheKeyException $e) {
    echo "Invalid cache key: " . $e->getMessage();
}

try {
    Cache::get('some_key');
} catch (CacheStorageException $e) {
    echo "Storage error: " . $e->getMessage();
} catch (CacheException $e) {
    echo "General cache error: " . $e->getMessage();
}
```

## Performance Tips

1. **Use appropriate TTL values** - longer for stable data, shorter for frequently changing data
2. **Implement cache warming** for critical data
3. **Use key prefixes** to organize and manage cache efficiently
4. **Monitor cache hit rates** to optimize your caching strategy
5. **Consider cache size limits** when using file storage
6. **Use dependency injection** in modern applications for better testability

## Examples

### Web Application Session Caching

```php
use WebFiori\Cache\Cache;

class SessionManager {
    public function __construct() {
        // Set prefix for session isolation
        Cache::withPrefix('session_');
    }
    
    public function getSession($sessionId) {
        return Cache::get($sessionId, function() use ($sessionId) {
            return $this->loadSessionFromDatabase($sessionId);
        }, 1800); // 30 minutes
    }
    
    public function saveSession($sessionId, $data) {
        Cache::set($sessionId, $data, 1800, true);
    }
    
    public function destroySession($sessionId) {
        Cache::delete($sessionId);
    }
    
    private function loadSessionFromDatabase($sessionId) {
        // Your database loading logic here
        return [];
    }
}
```

### Database Query Caching

```php
use WebFiori\Cache\Cache;

class UserRepository {
    public function findUser($id) {
        return Cache::get("user_{$id}", function() use ($id) {
            // Database query only runs on cache miss
            return $this->database->query("SELECT * FROM users WHERE id = ?", [$id]);
        }, 3600); // Cache for 1 hour
    }
    
    public function findUsersByRole($role) {
        return Cache::get("users_by_role_{$role}", function() use ($role) {
            return $this->database->query("SELECT * FROM users WHERE role = ?", [$role]);
        }, 1800); // Cache for 30 minutes
    }
    
    public function invalidateUserCache($id) {
        Cache::delete("user_{$id}");
        // Also invalidate related caches
        Cache::flush(); // Or more selective deletion
    }
}
```

### API Response Caching

```php
use WebFiori\Cache\Cache;

class ApiClient {
    public function getWeatherData($city) {
        return Cache::get("weather_{$city}", function() use ($city) {
            // External API call only on cache miss
            $response = file_get_contents("https://api.weather.com/data/{$city}");
            return json_decode($response, true);
        }, 900); // Cache for 15 minutes
    }
    
    public function getUserProfile($userId, $includePrivate = false) {
        $cacheKey = "profile_{$userId}_" . ($includePrivate ? 'full' : 'public');
        
        return Cache::get($cacheKey, function() use ($userId, $includePrivate) {
            return $this->fetchUserProfile($userId, $includePrivate);
        }, $includePrivate ? 300 : 3600); // Private data cached shorter
    }
}
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
- **Documentation**: This README and inline code documentation

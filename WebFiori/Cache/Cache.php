<?php
/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2024 Ibrahim BinAlshikh and Contributors
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace WebFiori\Cache;

use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;
use WebFiori\Cache\Exceptions\CacheDriverException;

/**
 * A class which is used to manage cache related operations
 */
class Cache {
    /**
     * Storage driver for this cache instance
     * @var Storage
     */
    private Storage $driver;
    
    /**
     * Global singleton instance for backward compatibility
     * @var Cache|null
     */
    private static ?Cache $inst = null;
    
    /**
     * Whether caching is enabled for this instance
     * @var bool
     */
    private bool $isEnabled;
    
    /**
     * Key prefix for this cache instance
     * @var string
     */
    private string $prefix;
    
    /**
     * Creates a new Cache instance with dependency injection.
     * 
     * @param Storage $driver The storage driver to use
     * @param bool $enabled Whether caching is enabled
     * @param string $prefix Key prefix for this cache instance
     */
    public function __construct(Storage $driver, bool $enabled = true, string $prefix = '') {
        $this->driver = $driver;
        $this->isEnabled = $enabled;
        $this->prefix = trim($prefix);
    }
    
    /**
     * Creates a new Cache instance (factory method for dependency injection).
     * 
     * @param Storage $driver The storage driver to use
     * @param bool $enabled Whether caching is enabled
     * @param string $prefix Key prefix for this cache instance
     * @return Cache A new cache instance
     */
    public static function create(Storage $driver, bool $enabled = true, string $prefix = ''): Cache {
        return new self($driver, $enabled, $prefix);
    }
    
    /**
     * Sets prefix for the cache instance and returns it for chaining.
     * 
     * @param string $prefix The prefix to use
     * @return Cache The cache instance (for static calls, returns global instance)
     */
    public static function withPrefix(string $prefix): Cache {
        $instance = self::getInst();
        $instance->prefix = trim($prefix);
        return $instance;
    }
    
    /**
     * Removes an item from the cache given its unique identifier.
     *
     * @param string $key The cache key
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function delete(string $key): void {
        self::validateKey($key);
        self::getDriver()->delete($key);
    }
    
    /**
     * Removes all items from the cache.
     */
    public static function flush(): void {
        self::getDriver()->flush(self::getPrefix());
    }
    
    /**
     * Gets the prefix from the global cache instance.
     * 
     * @return string The current prefix
     */
    public static function getPrefix(): string {
        return self::getInst()->prefix;
    }
    
    /**
     * Returns or creates a cache item given its key.
     *
     * @param string $key The unique identifier of the item
     * @param callable|null $generator A callback to generate data if cache miss
     * @param int $ttl Time to live of the item in seconds
     * @param array $params Parameters to pass to the generator callback
     * @return mixed The cached or generated data
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function get(string $key, callable $generator = null, int $ttl = 60, array $params = []) {
        self::validateKey($key);
        
        $data = self::getDriver()->read($key, self::getPrefix());

        if ($data !== null && $data !== false) {
            return $data;
        }

        if (!is_callable($generator)) {
            return null;
        }
        
        $newData = call_user_func_array($generator, $params);

        if (self::isEnabled()) {
            self::storeItem($key, $newData, $ttl);
        }

        return $newData;
    }
    
    /**
     * Returns storage engine which is used to store, read, update and delete items
     * from the cache.
     *
     * @return Storage
     */
    public static function getDriver(): Storage {
        return self::getInst()->driver;
    }
    
    /**
     * Reads an item from the cache and return its information.
     *
     * @param string $key The unique identifier of the item.
     *
     * @return Item|null If such item exist and not yet expired, an object
     * of type 'Item' is returned which has all cached item information. Other
     * than that, null is returned.
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function getItem(string $key): ?Item {
        self::validateKey($key);
        return self::getDriver()->readItem($key, self::getPrefix());
    }
    
    /**
     * Checks if the cache has in item given its unique identifier.
     *
     * @param string $key
     *
     * @return bool If the item exist and is not yet expired, true is returned.
     * Other than that, false is returned.
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function has(string $key): bool {
        self::validateKey($key);
        return self::getDriver()->has($key, self::getPrefix());
    }
    
    /**
     * Checks if caching is enabled or not.
     * 
     * @return bool True if enabled. False otherwise.
     */
    public static function isEnabled(): bool {
        return self::getInst()->isEnabled;
    }
    
    /**
     * Creates new item in the cache.
     *
     * Note that the item will only be added if it does not exist or already
     * expired or the override option is set to true in case it was already
     * created and not expired.
     *
     * @param string $key The unique identifier of the item.
     *
     * @param mixed $data The data that will be cached.
     *
     * @param int $ttl The time at which the data will be kept in the cache (in seconds).
     *
     * @param bool $override If cache item already exist which has given key and not yet
     * expired and this one is set to true, the existing item will be overridden by
     * provided data and ttl.
     *
     * @return bool If successfully added, the method will return true. False
     * otherwise.
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function set(string $key, $data, int $ttl = 60, bool $override = false): bool {
        self::validateKey($key);
        
        if (!self::has($key) || $override === true) {
            self::storeItem($key, $data, $ttl);
            return true;
        }

        return false;
    }
    
    /**
     * Sets storage engine which is used to store, read, update and delete items
     * from the cache.
     *
     * @param Storage $driver
     * @throws CacheDriverException If the driver is invalid
     */
    public static function setDriver(Storage $driver): void {
        if (!($driver instanceof Storage)) {
            throw new CacheDriverException(get_class($driver), 'setDriver', 0, 
                new \InvalidArgumentException('Driver must implement Storage interface'));
        }
        
        self::getInst()->driver = $driver;
    }
    
    /**
     * Enable or disable caching.
     * 
     * @param bool $enable If set to true, caching will be enabled. Other than
     * that, caching will be disabled.
     */
    public static function setEnabled(bool $enable): void {
        self::getInst()->isEnabled = $enable;
    }
    
    /**
     * Updates TTL of specific cache item.
     *
     * @param string $key The unique identifier of the item.
     *
     * @param int $ttl The new value for TTL.
     *
     * @return bool If item is updated, true is returned. Other than that, false
     * is returned.
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function setTTL(string $key, int $ttl): bool {
        self::validateKey($key);
        
        $item = self::getItem($key);

        if ($item === null) {
            return false;
        }
        
        $item->setTTL($ttl);
        self::getDriver()->store($item);

        return true;
    }
    
    /**
     * Creates and returns a single instance of the class.
     *
     * @return Cache
     */
    private static function getInst(): Cache {
        if (self::$inst === null) {
            self::$inst = new Cache(
                new FileStorage(__DIR__.DIRECTORY_SEPARATOR.'cache'),
                true,
                ''
            );
        }

        return self::$inst;
    }
    
    /**
     * Validates a cache key.
     *
     * @param string $key The key to validate
     * @throws InvalidCacheKeyException If the key is invalid
     */
    private static function validateKey(string $key): void {
        if (empty(trim($key))) {
            throw new InvalidCacheKeyException($key, 'Cache key cannot be empty');
        }
        
        if (strlen($key) > 250) {
            throw new InvalidCacheKeyException($key, 'Cache key exceeds maximum length of 250 characters');
        }
        
        // Check for invalid characters (control characters, etc.)
        if (preg_match('/[\x00-\x1F\x7F]/', $key)) {
            throw new InvalidCacheKeyException($key, 'Cache key contains invalid control characters');
        }
    }
    
    /**
     * Helper method to store an item with proper encryption handling.
     *
     * @param string $key The cache key
     * @param mixed $data The data to store
     * @param int $ttl Time to live in seconds
     */
    private static function storeItem(string $key, $data, int $ttl): void {
        // Use KeyManager for encryption key, but continue without encryption if not available
        $secretKey = '';
        try {
            $secretKey = KeyManager::getEncryptionKey();
        } catch (CacheException $e) {
            // If no key available, disable encryption for this item
            $item = new Item($key, $data, $ttl, '');
            $config = new SecurityConfig();
            $config->setEncryptionEnabled(false);
            $item->setSecurityConfig($config);
            $item->setPrefix(self::getPrefix());
            self::getDriver()->store($item);
            return;
        }
        
        $item = new Item($key, $data, $ttl, $secretKey);
        $item->setPrefix(self::getPrefix());
        self::getDriver()->store($item);
    }
}

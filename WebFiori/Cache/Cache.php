<?php

/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2024 WebFiori Framework
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace WebFiori\Cache;

use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\CacheStorageException;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;

/**
 * A class which is used to manage cache related operations.
 */
class Cache {
    /**
     * Storage driver for this cache instance.
     * @var Storage
     */
    private Storage $driver;

    /**
     * Whether caching is enabled for this instance.
     * @var bool
     */
    private bool $isEnabled;

    /**
     * Key prefix for this cache instance.
     * @var string
     */
    private string $prefix;

    /**
     * Creates a new Cache instance.
     *
     * @param Storage $driver The storage driver to use.
     * @param bool $enabled Whether caching is enabled.
     * @param string $prefix Key prefix for namespace isolation.
     */
    public function __construct(Storage $driver, bool $enabled = true, string $prefix = '') {
        $this->driver = $driver;
        $this->isEnabled = $enabled;
        $this->prefix = trim($prefix);
    }

    /**
     * Removes an item from the cache given its unique identifier.
     *
     * @param string $key The cache key.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function delete(string $key): void {
        self::validateKey($key);
        $this->driver->delete($this->prefix.$key);
    }

    /**
     * Removes all items from the cache. Respects the current prefix.
     */
    public function flush(): void {
        $this->driver->flush($this->prefix);
    }

    /**
     * Returns or creates a cache item given its key.
     *
     * If the item exists and is not expired, its data is returned. Otherwise,
     * the generator callback is invoked (if provided) and its return value is
     * cached and returned. If storage fails during write, the generated data
     * is still returned but will not be cached.
     *
     * @param string $key The unique identifier of the item.
     * @param callable|null $generator A callback to generate data if cache miss.
     * @param int $ttl Time to live of the item in seconds.
     * @param array $params Parameters to pass to the generator callback.
     * @return mixed The cached or generated data.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function get(string $key, ?callable $generator = null, int $ttl = 60, array $params = []) {
        self::validateKey($key);

        $item = $this->driver->readItem($key, $this->prefix);

        if ($item !== null) {
            try {
                return $item->getDataDecrypted();
            } catch (CacheException $e) {
                // Decryption failed, treat as cache miss
            }
        }

        if (!is_callable($generator)) {
            return null;
        }

        $newData = call_user_func_array($generator, $params);

        if ($this->isEnabled) {
            try {
                $this->storeItem($key, $newData, $ttl);
            } catch (CacheStorageException $e) {
                // Storage failed, but still return the generated data.
                // The data just won't be cached.
            }
        }

        return $newData;
    }

    /**
     * Returns the storage engine used by this cache instance.
     *
     * @return Storage
     */
    public function getDriver(): Storage {
        return $this->driver;
    }

    /**
     * Reads an item from the cache and returns its information.
     *
     * @param string $key The unique identifier of the item.
     * @return Item|null If the item exists and is not expired, an Item object
     * is returned. Otherwise, null is returned.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function getItem(string $key): ?Item {
        self::validateKey($key);

        return $this->driver->readItem($key, $this->prefix);
    }

    /**
     * Gets the prefix of this cache instance.
     *
     * @return string The current prefix.
     */
    public function getPrefix(): string {
        return $this->prefix;
    }

    /**
     * Checks if the cache has an item given its unique identifier.
     *
     * @param string $key The cache key.
     * @return bool True if the item exists and is not expired.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function has(string $key): bool {
        self::validateKey($key);

        return $this->driver->has($key, $this->prefix);
    }

    /**
     * Checks if caching is enabled.
     *
     * @return bool True if enabled.
     */
    public function isEnabled(): bool {
        return $this->isEnabled;
    }

    /**
     * Removes all expired items from the cache storage.
     *
     * Useful for periodic cleanup via a cron job or maintenance script.
     *
     * @return int The number of expired items that were removed.
     */
    public function purgeExpired(): int {
        return $this->driver->purgeExpired();
    }

    /**
     * Creates a new item in the cache.
     *
     * The item will only be added if it does not exist or is already expired,
     * unless the override option is set to true.
     *
     * @param string $key The unique identifier of the item.
     * @param mixed $data The data that will be cached.
     * @param int $ttl The time the data will be kept in cache (in seconds).
     * @param bool $override If true, an existing non-expired item will be overwritten.
     * @return bool True if successfully stored. False if the item already exists
     * and override is false, or if storage fails.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function set(string $key, $data, int $ttl = 60, bool $override = false): bool {
        self::validateKey($key);

        if (!$this->has($key) || $override === true) {
            try {
                $this->storeItem($key, $data, $ttl);

                return true;
            } catch (CacheStorageException $e) {
                return false;
            }
        }

        return false;
    }

    /**
     * Sets the storage engine for this cache instance.
     *
     * @param Storage $driver The storage driver.
     */
    public function setDriver(Storage $driver): void {
        $this->driver = $driver;
    }

    /**
     * Enable or disable caching.
     *
     * @param bool $enable True to enable, false to disable.
     */
    public function setEnabled(bool $enable): void {
        $this->isEnabled = $enable;
    }

    /**
     * Updates TTL of a specific cache item.
     *
     * @param string $key The unique identifier of the item.
     * @param int $ttl The new value for TTL.
     * @return bool True if the item was updated. False if the item does not exist.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    public function setTTL(string $key, int $ttl): bool {
        self::validateKey($key);

        $item = $this->getItem($key);

        if ($item === null) {
            return false;
        }

        $item->setTTL($ttl);
        $this->driver->store($item);

        return true;
    }

    /**
     * Returns a new Cache instance with the given prefix, sharing the same
     * driver and enabled state. The current instance is not modified.
     *
     * @param string $prefix The prefix to use.
     * @return Cache A new cache instance with the given prefix.
     */
    public function withPrefix(string $prefix): Cache {
        return new self($this->driver, $this->isEnabled, $prefix);
    }

    /**
     * Helper method to store an item with proper encryption handling.
     *
     * @param string $key The cache key.
     * @param mixed $data The data to store.
     * @param int $ttl Time to live in seconds.
     * @throws CacheStorageException If storage fails.
     */
    private function storeItem(string $key, $data, int $ttl): void {
        $secretKey = '';
        try {
            $secretKey = KeyManager::getEncryptionKey();
        } catch (CacheException $e) {
            $item = new Item($key, $data, $ttl, '');
            $config = new SecurityConfig();
            $config->setEncryptionEnabled(false);
            $item->setSecurityConfig($config);
            $item->setPrefix($this->prefix);
            $this->driver->store($item);

            return;
        }

        $item = new Item($key, $data, $ttl, $secretKey);
        $item->setPrefix($this->prefix);
        $this->driver->store($item);
    }

    /**
     * Validates a cache key.
     *
     * @param string $key The key to validate.
     * @throws InvalidCacheKeyException If the key is invalid.
     */
    private static function validateKey(string $key): void {
        if (empty(trim($key))) {
            throw new InvalidCacheKeyException($key, 'Cache key cannot be empty');
        }

        if (strlen($key) > 250) {
            throw new InvalidCacheKeyException($key, 'Cache key exceeds maximum length of 250 characters');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $key)) {
            throw new InvalidCacheKeyException($key, 'Cache key contains invalid control characters');
        }
    }
}

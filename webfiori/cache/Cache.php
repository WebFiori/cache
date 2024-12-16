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
namespace webfiori\cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * A class which is used to manage cache related operations
 */
class Cache implements CacheItemPoolInterface {
    /**
     *
     * @var Storage
     */
    private $driver;
    private static $inst;
    private $isEnabled;
    private $deferredItems;
    /**
     * Removes an item from the cache given its unique identifier.
     *
     * @param string $key
     */
    public function delete(string $key) {
        $this->getStorage()->delete($key);
        if (isset($this->deferredItems[$key])) {
            unset($this->deferredItems[$key]);
        }
    }
    /**
     * Removes all items from the cache.
     */
    public function flush() {
        $this->getStorage()->flush();
        $this->deferredItems = [];
    }
    /**
     * Returns or creates a cache item given its key.
     *
     * @param string $key The unique identifier of the item.
     *
     * @param callable $generator A callback which is used as a fallback to
     * create new cache entry or re-create an existing one if it was expired.
     * This callback must return the data that will be cached.
     *
     * @param int $ttl Time to live of the item in seconds.
     *
     * @param array $params Any additional parameters to be passed to the callback
     * which is used to generate cache data.
     * @return null
     */
    public function get(string $key, callable $generator = null, int $ttl = 60, array $params = []) {
        if (isset($this->deferredItems[$key])) {
            return $this->deferredItems[$key]->getData();
        }
        $data = $this->getStorage()->read($key);

        if ($data !== null && $data !== false) {
            return $data;
        }

        if (!is_callable($generator)) {
            return null;
        }
        $newData = call_user_func_array($generator, $params);

        if ($this->isEnabled()) {
            $item = new Item($key, $newData, $ttl, defined('CACHE_SECRET') ? CACHE_SECRET : '');
            $this->saveDeferred($item);
        }

        return $newData;
    }
    /**
     * Returns storage engine which is used to store, read, update and delete items
     * from the cache.
     *
     * @return Storage
     */
    public function getStorage() : Storage {
        return $this->driver;
    }
    /**
     * Reads an item from the cache and return its information.
     *
     * @param string $key The unique identifier of the item.
     *
     * @return Item|null If such item exist and not yet expired, an object
     * of type 'Item' is returned which has all cached item information. Other
     * than that, null is returned.
     */
    public function getItem(string $key) {
        if (isset($this->deferredItems[$key])) {
            return $this->deferredItems[$key];
        }
        return $this->getStorage()->readItem($key);
    }
    /**
     * Checks if the cache has in item given its unique identifier.
     *
     * @param string $key
     *
     * @return bool If the item exist and is not yet expired, true is returned.
     * Other than that, false is returned.
     */
    public function has(string $key) : bool {
        return $this->getStorage()->has($key);
    }
    /**
     * Checks if caching is enabled or not.
     * 
     * @return bool True if enabled. False otherwise.
     */
    public function isEnabled() : bool {
        return $this->isEnabled;
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
     */
    public function set(string $key, $data, int $ttl = 60, bool $override = false) : bool {
        if (!$this->has($key) || $override === true) {
            $item = new Item($key, $data, $ttl, defined('CACHE_SECRET') ? CACHE_SECRET : '');
            $this->getStorage()->store($item);

            return true;
        }

        return false;
    }
    /**
     * Sets storage engine which is used to store, read, update and delete items
     * from the cache.
     *
     * @param Storage $driver
     */
    public function setDriver(Storage $driver) {
        $this->driver = $driver;
    }
    /**
     * Enable or disable caching.
     * 
     * @param bool $enable If set to true, caching will be enabled. Other than
     * that, caching will be disabled.
     */
    public function setEnabled(bool $enable) {
        $this->isEnabled = $enable;
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
     */
    public function setTTL(string $key, int $ttl) {
        $item = $this->getItem($key);

        if ($item === null) {
            return false;
        }
        $item->setTTL($ttl);
        $this->getStorage()->store($item);

        return true;
    }
    public function __construct() {
        $this->driver = new FileStorage(__DIR__.DIRECTORY_SEPARATOR.'cache');
        $this->isEnabled = true;
        $this->deferredItems = [];
    }
    /**
     * Creates and returns a single instance of the class.
     *
     * @return Cache
     */
    public static function getInstance() : Cache {
        if (self::$inst === null) {
            self::$inst = new Cache();
        }

        return self::$inst;
    }


    #[\Override]
    public function clear(): bool {
        $this->flush();
    }

    #[\Override]
    public function commit(): bool {
        foreach ($this->deferredItems as $item) {
            $this->getStorage()->store($item);
        }
    }

    #[\Override]
    public function deleteItem(string $key): bool {
        $this->delete($key);
    }

    #[\Override]
    public function deleteItems(array $keys): bool {
        foreach ($keys as $key) {
            $this->deleteItem($key);
        }
    }

    #[\Override]
    public function getItems(string $keys = []): iterable {
        $itemsArr = [];
        foreach ($keys as $key) {
            $itemsArr[$key] = $this->getItem($key);
        }
        return $itemsArr;
    }

    #[\Override]
    public function hasItem(string $key): bool {
        return $this->getItem($key) !== null;
    }

    #[\Override]
    public function save(CacheItemInterface $item): bool {
        $this->getStorage()->store($item);
    }

    #[\Override]
    public function saveDeferred(CacheItemInterface $item): bool {
        $this->deferredItems[$item->getKey()] = $item;
    }
}

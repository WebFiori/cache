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

/**
 * A class which is used to manage cache related operations
 */
class Cache {
    /**
     *
     * @var Storage
     */
    private $driver;
    private static $inst;
    private $isEnabled;
    /**
     * Removes an item from the cache given its unique identifier.
     *
     * @param string $key
     */
    public static function delete(string $key) {
        self::getDriver()->deleteItem($key);
        self::getDriver()->deleteDeferredItem($key);
    }
    /**
     * Removes all items from the cache.
     */
    public static function clear() {
        self::getDriver()->clear();
        self::getDriver()->clearDeferredItems();
    }
    /**
     * Returns or creates a cache item given its key.
     *
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
     * 
     * @return mixed
     */
    public static function get(string $key, $default = null, int $ttl = 60, array $params = []) : mixed {
        $item = self::getDriver()->getItem($key);
        $data = $default;
        if ($item !== null) {
           $decrypted = $item->getDataDecrypted();
           if ($decrypted !== false) {
               return $decrypted;
           }
           
        }

        if (is_callable($default)) {
            $data = call_user_func_array($default, $params);
        }

        if (self::isEnabled()) {
            $item = new Item($key, $data, $ttl, defined('CACHE_SECRET') ? CACHE_SECRET : '');
            
            if ($item->getTTL() != -1) {
                self::getDriver()->saveDeferred($item);
            }
        }

        return $data;
    }
    /**
     * Returns storage engine which is used to store, read, update and delete items
     * from the cache.
     *
     * @return Storage
     */
    public static function getDriver() : Storage {
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
     */
    public static function getItem(string $key) : Item {
        return self::getDriver()->getItem($key);
    }
    public function getItems(array $keys) : iterable {
        return self::getDriver()->getItems($keys);
    }
    /**
     * Checks if the cache has in item given its unique identifier.
     *
     * @param string $key
     *
     * @return bool If the item exist and is not yet expired, true is returned.
     * Other than that, false is returned.
     */
    public static function has(string $key) : bool {
        return self::getDriver()->hasItem($key) || self::getDriver()->hasDeferred($key);
    }
    public static function deleteItems(array $keys) {
        self::getDriver()->deleteItems($keys);
        self::getDriver()->deleteDeferredItems($keys);
    }
    /**
     * 
     * @return bool
     */
    public static function commit() : bool {
        return self::getDriver()->commit();
    }
    /**
     * Checks if caching is enabled or not.
     * 
     * @return bool True if enabled. False otherwise.
     */
    public static function isEnabled() : bool {
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
     */
    public static function save(string $key, $data, int $ttl = 60, bool $override = false) : bool {
        if (!self::has($key) || $override === true) {
            $item = new Item($key, $data, $ttl, defined('CACHE_SECRET') ? CACHE_SECRET : '');
            self::getDriver()->save($item);

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
    public static function setDriver(Storage $driver) {
        self::getInst()->driver = $driver;
    }
    /**
     * Enable or disable caching.
     * 
     * @param bool $enable If set to true, caching will be enabled. Other than
     * that, caching will be disabled.
     */
    public static function setEnabled(bool $enable) {
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
     */
    public static function setTTL(string $key, int $ttl) {
        $item = self::getItem($key);

        if ($item->getTTL() == -1) {
            
            return false;
        }
        $item->setTTL($ttl);
        self::getDriver()->saveDeferred($item);

        return true;
    }
    /**
     * Creates and returns a single instance of the class.
     *
     * @return Cache
     */
    private static function getInst() : Cache {
        if (self::$inst === null) {
            self::$inst = new Cache();
            self::setDriver(new FileStorage(__DIR__.DIRECTORY_SEPARATOR.'cache'));
            self::setEnabled(true);
        }

        return self::$inst;
    }
}

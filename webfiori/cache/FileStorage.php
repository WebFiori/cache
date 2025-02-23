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

use InvalidArgumentException;

/**
 * File based cache storage engine.
 */
class FileStorage implements Storage {
    private $cacheDir;
    private $data;
    /**
     * Creates new instance of the class.
     *
     * The default location for cache files will be in [APP_PATH]/sto/cache.
     * To use a custom path, the developer can define the constant CACHE_PATH.
     * 
     * @param string $storagePath The location at which cache items will be
     * stored at.
     */
    public function __construct(string $storagePath) {
        $this->setPath($storagePath);
    }
    /**
     * Removes an item from the cache.
     *
     * @param string $key The key of the item.
     */
    public function delete(string $key) {
        $filePath = $this->getPath().DIRECTORY_SEPARATOR.md5($key).'.cache';

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    /**
     * Removes all cached items.
     * 
     * @param string|null $prefix An optional prefix. If provided, the method will
     * only delete the items which has given prefix.
     */
    public function flush(?string $prefix) {
        $files = glob($this->cacheDir.DIRECTORY_SEPARATOR.$prefix.'*.cache');

        foreach ($files as $file) {
            unlink($file);
        }
    }
    /**
     * Returns a string that represents the path to the folder which is used to
     * create cache files.
     *
     * @return string A string that represents the path to the folder which is used to
     * create cache files.
     */
    public function getPath() : string {
        return $this->cacheDir;
    }
    /**
     * Checks if an item exist in the cache.
     * @param string $key The value of item key.
     *
     * @return bool Returns true if given
     * key exist in the cache and not yet expired.
     */
    public function has(string $key, ?string $prefix): bool {
        return $this->read($key, $prefix) !== null;
    }
    /**
     * Reads and returns the data stored in cache item given its key.
     *
     * @param string $key The key of the item.
     *
     * @return mixed|null If cache item is not expired, its data is returned. Other than
     * that, null is returned.
     */
    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);

        if ($item !== null) {
            return $item->getDataDecrypted();
        }

        return null;
    }
    /**
     * Reads cache item as an object given its key.
     *
     * @param string $key The unique identifier of the item.
     *
     * @return Item|null If cache item exist and is not expired,
     * an object of type 'Item' is returned. Other than
     * that, null is returned.
     */
    public function readItem(string $key, ?string $prefix) {
        $this->initData($key, $prefix);
        $now = time();

        if ($now > $this->data['expires']) {
            $this->delete($key);

            return null;
        }
        $item = new Item($key, $this->data['data'], $this->data['ttl'], defined('CACHE_SECRET') ? CACHE_SECRET : '');
        $item->setCreatedAt($this->data['created_at']);

        return $item;
    }
    /**
     * Sets the path to the folder which is used to create cache files.
     *
     * @param string $path
     * 
     * @throws InvalidArgumentException
     */
    public function setPath(string $path) {
        $this->cacheDir = $path;
    }
    /**
     * Store an item into the cache.
     *
     * @param Item $item An item that will be added to the cache.
     */
    public function store(Item $item) {
        if ($item->getTTL() > 0) {
            $filePath = $this->getPath().DIRECTORY_SEPARATOR.$item->getPrefix().md5($item->getKey()).'.cache';
            $encryptedData = $item->getDataEncrypted();
            $storageFolder = $this->getPath();

                if (!is_dir($storageFolder)) {
                    if (!mkdir($storageFolder, 0755, true)) {
                        throw new InvalidArgumentException("Invalid cache path: '".$storageFolder."'.");
                    }
                }
            file_put_contents($filePath, serialize([
                'data' => $encryptedData,
                'created_at' => time(),
                'ttl' => $item->getTTL(),
                'expires' => $item->getExpiryTime(),
                'key' => $item->getKey()
            ]));
        }
    }
    private function initData(string $key, string $prefix) {
        $filePath = $this->cacheDir.DIRECTORY_SEPARATOR.$prefix.md5($key).'.cache';

        if (!file_exists($filePath)) {
            $this->data = [
                'expires' => 0,
                'ttl' => 0,
                'data' => null,
                'created_at' => 0,
                'key' => ''
            ];

            return ;
        }

        $this->data = unserialize(file_get_contents($filePath));
    }
}

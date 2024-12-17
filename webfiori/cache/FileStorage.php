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
use Override;
use Psr\Cache\CacheItemInterface;

/**
 * File based cache storage engine.
 */
class FileStorage extends Storage {
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
        parent::__construct();
        $this->setPath($storagePath);
    }
    /**
     * Removes an item from the cache.
     *
     * @param string $key The key of the item.
     */
    public function deleteItem(string $key) : bool {
        $filePath = $this->getPath().DIRECTORY_SEPARATOR.md5($key).'.cache';

        if (file_exists($filePath)) {
            unlink($filePath);
            return true;
        }
        return false;
    }
    /**
     * Removes all cached items.
     *
     */
    public function clear() : bool {
        $this->clearDeferredItems();
        $files = glob($this->cacheDir.DIRECTORY_SEPARATOR.'*.cache');

        foreach ($files as $file) {
            unlink($file);
        }
        return true;
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
    public function hasItem(string $key): bool {
        $filePath = $this->cacheDir.DIRECTORY_SEPARATOR.md5($key).'.cache';

        return file_exists($filePath);
    }
    /**
     * Reads and returns the data stored in cache item given its key.
     *
     * @param string $key The key of the item.
     *
     * @return mixed|null If cache item is not expired, its data is returned. Other than
     * that, null is returned.
     */
    public function read(string $key) : mixed {
        $item = $this->getItem($key);

        if ($item->getTTL() != -1) {
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
    public function getItem(string $key) : Item {
        $item = $this->getDeferredItem($key);
        if ($item !== null) {
            return $item;
        }
        $this->initData($key);
        $now = time();

        if ($this->data['expires'] != 0 && $now > $this->data['expires']) {
            Cache::delete($key);

        }
        $item = new Item($key, $this->data['data'], $this->data['ttl'], defined('CACHE_SECRET') ? CACHE_SECRET : '');
        if ($item->getTTL() != -1) {
            //Update is hit
            $item->setData($this->data['data']);
        }
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
    public function save(CacheItemInterface $item) : bool {
        
        if ($item instanceof Item && $item->getTTL() >= 0) {
            $filePath = $this->getPath().DIRECTORY_SEPARATOR.md5($item->getKey()).'.cache';
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
            return true;
        }
        return false;
    }
    private function initData(string $key) {
        $filePath = $this->cacheDir.DIRECTORY_SEPARATOR.md5($key).'.cache';

        if (!file_exists($filePath)) {
            $this->data = [
                'expires' => 0,
                'ttl' => -1,
                'data' => null,
                'created_at' => 0,
                'key' => ''
            ];

            return ;
        }

        $this->data = unserialize(file_get_contents($filePath));
    }

    #[Override]
    public function deleteItems(array $keys): bool {
        $removed = true;
        
        foreach ($keys as $key) {
            $removed = $removed && $this->deleteItem($key);
        }
        return $removed;
    }

    #[Override]
    public function getItems(array $keys = []): iterable {
        $items = [];
        foreach ($keys as $key) {
            $items[] = $this->getItem($key);
        }
        return $items;
    }
}

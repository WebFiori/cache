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

use WebFiori\Cache\Exceptions\CacheStorageException;
use WebFiori\Cache\Exceptions\CacheException;

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
     * @throws CacheStorageException If the storage path is invalid
     */
    public function __construct(string $storagePath) {
        $this->setPath($storagePath);
    }
    
    /**
     * Removes an item from the cache.
     *
     * @param string $key The key of the item.
     * @throws CacheStorageException If file deletion fails
     */
    public function delete(string $key) {
        $filePath = $this->getPath().DIRECTORY_SEPARATOR.md5($key).'.cache';

        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                throw new CacheStorageException("Failed to delete cache file: {$filePath}");
            }
        }
    }
    
    /**
     * Removes all cached items.
     * 
     * @param string|null $prefix An optional prefix. If provided, the method will
     * only delete the items which has given prefix.
     * @throws CacheStorageException If flush operation fails
     */
    public function flush(?string $prefix) {
        $files = glob($this->cacheDir.DIRECTORY_SEPARATOR.$prefix.'*.cache');

        foreach ($files as $file) {
            if (!unlink($file)) {
                throw new CacheStorageException("Failed to delete cache file during flush: {$file}");
            }
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
     * @throws CacheStorageException If reading fails
     */
    public function read(string $key, ?string $prefix) {
        $item = $this->readItem($key, $prefix);

        if ($item !== null) {
            try {
                return $item->getDataDecrypted();
            } catch (CacheException $e) {
                // If decryption fails, delete the corrupted item and return null
                $this->delete($key);
                return null;
            }
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
     * @throws CacheStorageException If reading fails
     */
    public function readItem(string $key, ?string $prefix) {
        $this->initData($key, $prefix);
        $now = time();

        if ($now > $this->data['expires']) {
            $this->delete($key);

            return null;
        }
        
        // Use KeyManager for encryption key if not provided, but handle gracefully if not available
        $secretKey = '';
        $encryptionEnabled = true;
        try {
            $secretKey = KeyManager::getEncryptionKey();
        } catch (CacheException $e) {
            // If no key available, assume encryption is disabled
            $encryptionEnabled = false;
        }
        
        // Create item with the stored encrypted/serialized data
        $item = new Item($key, $this->data['data'], $this->data['ttl'], $secretKey);
        $item->setCreatedAt($this->data['created_at']);
        $item->setPrefix($prefix ?? '');
        
        // Configure encryption based on what was stored
        $wasEncrypted = $this->data['encrypted'] ?? true;
        if (!$wasEncrypted || !$encryptionEnabled) {
            $config = new SecurityConfig();
            $config->setEncryptionEnabled(false);
            $item->setSecurityConfig($config);
        }
        
        // Mark data as encrypted only if it was actually encrypted when stored
        $item->setDataIsEncrypted($wasEncrypted);
        
        // Mark that this data came from storage
        $item->setDataFromStorage(true);

        return $item;
    }
    
    /**
     * Sets the path to the folder which is used to create cache files.
     *
     * @param string $path
     * 
     * @throws CacheStorageException If the path is invalid or not writable
     */
    public function setPath(string $path) {
        if (empty(trim($path))) {
            throw new CacheStorageException("Cache path cannot be empty");
        }
        
        // Check if path exists and is writable, or if parent directory is writable for creation
        if (file_exists($path)) {
            if (!is_dir($path)) {
                throw new CacheStorageException("Cache path exists but is not a directory: {$path}");
            }
            if (!is_writable($path)) {
                throw new CacheStorageException("Cache path is not writable: {$path}");
            }
        } else {
            $parentDir = dirname($path);
            if (!is_writable($parentDir)) {
                throw new CacheStorageException("Cannot create cache directory, parent directory is not writable: {$parentDir}");
            }
        }
        
        $this->cacheDir = $path;
    }
    
    /**
     * Store an item into the cache.
     *
     * @param Item $item An item that will be added to the cache.
     * @throws CacheStorageException If storage operations fail
     */
    public function store(Item $item) {
        if ($item->getTTL() > 0) {
            $securityConfig = $item->getSecurityConfig();
            $filePath = $this->getPath().DIRECTORY_SEPARATOR.$item->getPrefix().md5($item->getKey()).'.cache';
            $encryptedData = $item->getDataEncrypted();
            $storageFolder = $this->getPath();

            if (!is_dir($storageFolder) && !mkdir($storageFolder, $securityConfig->getDirectoryPermissions(), true)) {
                throw new CacheStorageException("Failed to create cache directory: '{$storageFolder}'");
            }
            
            // Create temporary file for atomic write
            $tempFile = $filePath . '.tmp';
            $dataSer = serialize([
                'data' => $encryptedData,
                'created_at' => time(),
                'ttl' => $item->getTTL(),
                'expires' => $item->getExpiryTime(),
                'key' => $item->getKey(),
                'encrypted' => $securityConfig->isEncryptionEnabled()
            ]);
            
            if (file_put_contents($tempFile, $dataSer, LOCK_EX) === false) {
                throw new CacheStorageException("Failed to write cache file: {$tempFile}");
            }
            
            // Set restrictive permissions and atomic rename
            if (!chmod($tempFile, $securityConfig->getFilePermissions())) {
                unlink($tempFile);
                throw new CacheStorageException("Failed to set permissions on cache file: {$tempFile}");
            }
            
            if (!rename($tempFile, $filePath)) {
                unlink($tempFile);
                throw new CacheStorageException("Failed to create cache file: {$filePath}");
            }
        }
    }
    
    /**
     * Initializes data for a cache item.
     * 
     * @param string $key The cache key
     * @param string $prefix The key prefix
     * @throws CacheStorageException If file reading or unserialization fails
     */
    private function initData(string $key, ?string $prefix) {
        $filePath = $this->cacheDir.DIRECTORY_SEPARATOR.($prefix ?? '').md5($key).'.cache';

        if (!file_exists($filePath)) {
            $this->data = [
                'expires' => 0,
                'ttl' => 0,
                'data' => null,
                'created_at' => 0,
                'key' => '',
                'encrypted' => true // Default to encrypted for backward compatibility
            ];

            return ;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new CacheStorageException("Failed to read cache file: {$filePath}");
        }
        
        try {
            $this->data = @unserialize($content);
            if ($this->data === false) {
                throw new CacheStorageException("Failed to unserialize cache data from: {$filePath}");
            }
        } catch (Error | Exception $e) {
            throw new CacheStorageException("Failed to unserialize cache data from: {$filePath}");
        }
        
        // Handle backward compatibility - if 'encrypted' field doesn't exist, assume encrypted
        if (!isset($this->data['encrypted'])) {
            $this->data['encrypted'] = true;
        }
    }
}

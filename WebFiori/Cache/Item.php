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

use InvalidArgumentException;
use WebFiori\Cache\Exceptions\CacheException;
use Exception;

/**
 * A class which represent a cache item.
 */
class Item {
    private $createdAt;
    private $data;
    private $key;
    private $secretKey;
    private $timeToLive = 0;
    private $prefix;
    private ?SecurityConfig $securityConfig = null;
    private bool $dataIsEncrypted = false;
    private bool $dataFromStorage = false;
    
    /**
     * Creates new instance of the class.
     *
     * @param string $key The unique key which is used to identify cache item.
     * Its used in storing, update and deletion of cache item.
     *
     * @param mixed $data The data that will be cached.
     *
     * @param int $ttl The time at which the item will be kept in the cache in seconds.
     *
     * @param string $secretKey A secret key which is used during encryption
     * and decryption phases of cache storage and retrieval. If empty, will use KeyManager.
     * 
     * @throws InvalidArgumentException If the secret key is provided but invalid
     */
    public function __construct(string $key = 'item', $data = '', int $ttl = 60, string $secretKey = '') {
        $this->securityConfig = new SecurityConfig();
        
        $this->setKey($key);
        $this->setTTL($ttl);
        $this->setData($data);
        $this->setCreatedAt(time());
        $this->setPrefix('');
        
        // Set secret after security config is initialized
        $this->setSecret($secretKey);
    }
    
    /**
     * Generates a cryptographic secure key.
     *
     * The generated key can be used to encrypt sensitive data. Note that the generated
     * key must be kept in some kind of secure configuration file in order to 
     * use it later.
     *
     * @return string
     */
    public static function generateKey() : string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Returns the time at which the item was created at.
     *
     * The value returned by the method is Unix timestamp.
     *
     * @return int An integer that represents Unix timestamp in seconds.
     */
    public function getCreatedAt() : int {
        return $this->createdAt;
    }
    
    /**
     * Returns the data of cache item.
     *
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }
    
    /**
     * Returns cache item data after performing decryption on it.
     *
     * This method should be used when reading encrypted data from storage.
     * If the data is not encrypted (encryption disabled), it will unserialize the data.
     *
     * @return mixed
     * @throws CacheException If decryption fails
     */
    public function getDataDecrypted() {
        if (!$this->dataIsEncrypted) {
            // Data is not encrypted
            if ($this->dataFromStorage) {
                // Data came from storage and is serialized
                return unserialize($this->getData());
            } else {
                // Fresh data, return as-is
                return $this->getData();
            }
        }
        
        // Data is encrypted, decrypt it first then unserialize
        $decryptedData = $this->decrypt($this->getData());
        return unserialize($decryptedData);
    }
    
    /**
     * Returns cache data after performing encryption on it.
     *
     * @return string
     * @throws CacheException If encryption fails
     */
    public function getDataEncrypted() : string {
        $serializedData = serialize($this->getData());
        
        if (!$this->securityConfig || !$this->securityConfig->isEncryptionEnabled()) {
            return $serializedData;
        }
        
        return $this->encrypt($serializedData);
    }
    
    /**
     * Returns the time at which cache item will expire as Unix timestamp.
     *
     * The method will add the time at which the item was created at to TTL and
     * return the value.
     *
     * @return int The time at which cache item will expire as Unix timestamp.
     */
    public function getExpiryTime() : int {
        return $this->getCreatedAt() + $this->getTTL();
    }
    
    /**
     * Gets the key of the item.
     *
     * The key acts as a unique identifier for cache items.
     * 
     * @return string A string that represents the key.
     */
    public function getKey() : string {
        return $this->key;
    }
    
    /**
     * Returns the value of the key which is used in encrypting cache data.
     *
     * @return string The value of the key which is used in encrypting cache data.
     * Default return value is empty string.
     */
    public function getSecret() : string {
        return $this->secretKey;
    }
    
    /**
     * Returns the duration at which the item will be kept in cache in seconds.
     *
     * @return int The duration at which the item will be kept in cache in seconds.
     */
    public function getTTL() : int {
        return $this->timeToLive;
    }
    
    /**
     * Sets the time at which the item was created at.
     *
     * @param int $time An integer that represents Unix timestamp in seconds.
     * Must be a positive value.
     */
    public function setCreatedAt(int $time) {
        if ($time > 0) {
            $this->createdAt = $time;
        }
    }
    
    /**
     * Sets the data of the item.
     *
     * This represents the data that will be stored or retrieved.
     *
     * @param mixed $data
     */
    public function setData($data) {
        $this->data = $data;
    }
    
    /**
     * Sets the key of the item.
     *
     * The key acts as a unique identifier for cache items.
     *
     * @param string $key A string that represents the key.
     */
    public function setKey(string $key) {
        $this->key = $key;
    }
    
    /**
     * Returns the prefix that will be appended to the key.
     * 
     * @return string If no prefix is set, empty string is returned.
     */
    public function getPrefix() : string {
        return $this->prefix;
    }
    
    /**
     * Sets the prefix that will be appended to the key.
     * 
     * @param string $prefix
     */
    public function setPrefix(string $prefix) {
        $this->prefix = trim($prefix.'');
    }
    
    /**
     * Sets the value of the key which is used in encrypting cache data.
     *
     * @param string $secret A cryptographic key which is used to encrypt
     * cache data. To generate one, the method Item::generateKey() can be used.
     * 
     * @throws InvalidArgumentException If the key is invalid and encryption is enabled
     */
    public function setSecret(string $secret) {
        if (isset($this->securityConfig) && $this->securityConfig->isEncryptionEnabled() && 
            !empty($secret) && !$this->isValidEncryptionKey($secret)) {
            throw new InvalidArgumentException('Invalid encryption key provided. Must be 64 hexadecimal characters.');
        }
        $this->secretKey = $secret;
    }
    
    /**
     * Sets the duration at which the item will be kept in cache in seconds.
     *
     * @param int $ttl Time-to-live of the item in cache.
     */
    public function setTTL(int $ttl) {
        if ($ttl >= 0) {
            $this->timeToLive = $ttl;
        }
    }
    
    /**
     * Sets whether the current data is encrypted.
     * 
     * @param bool $encrypted True if data is encrypted
     */
    public function setDataIsEncrypted(bool $encrypted): void {
        $this->dataIsEncrypted = $encrypted;
    }
    
    /**
     * Sets whether the data came from storage.
     * 
     * @param bool $fromStorage True if data came from storage
     */
    public function setDataFromStorage(bool $fromStorage): void {
        $this->dataFromStorage = $fromStorage;
    }
    
    /**
     * Checks if the current data is encrypted.
     * 
     * @return bool True if data is encrypted
     */
    public function isDataEncrypted(): bool {
        return $this->dataIsEncrypted;
    }
    
    /**
     * Gets the security configuration.
     * 
     * @return SecurityConfig
     */
    public function getSecurityConfig(): SecurityConfig {
        return $this->securityConfig;
    }
    
    /**
     * Sets the security configuration.
     * 
     * @param SecurityConfig $config
     */
    public function setSecurityConfig(SecurityConfig $config): void {
        $this->securityConfig = $config;
    }
    
    /**
     * Validates if an encryption key is properly formatted.
     * 
     * @param string $key The key to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidEncryptionKey(string $key): bool {
        return strlen($key) === 64 && ctype_xdigit($key);
    }
    
    /**
     * Decrypts encrypted data.
     * 
     * @param string $data The encrypted data to decrypt
     * @return string The decrypted data
     * @throws CacheException If decryption fails
     */
    private function decrypt($data): string {
        $encKey = $this->getEffectiveEncryptionKey();
        
        try {
            $decodedData = base64_decode($data, true);
            if ($decodedData === false) {
                throw new CacheException('Invalid base64 encoded data');
            }
            
            $algorithm = $this->securityConfig ? $this->securityConfig->getEncryptionAlgorithm() : 'aes-256-cbc';
            $ivLength = openssl_cipher_iv_length($algorithm);
            
            if (strlen($decodedData) < $ivLength) {
                throw new CacheException('Invalid encrypted data format');
            }
            
            $iv = substr($decodedData, 0, $ivLength);
            $encryptedData = substr($decodedData, $ivLength);
            
            $decrypted = openssl_decrypt($encryptedData, $algorithm, $encKey, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                throw new CacheException('Decryption failed');
            }
            
            return $decrypted;
        } catch (Exception $e) {
            throw new CacheException('Decryption error: ' . $e->getMessage());
        }
    }
    
    /**
     * Encrypts data.
     * 
     * @param string $data The data to encrypt
     * @return string The encrypted data
     * @throws CacheException If encryption fails
     */
    private function encrypt($data): string {
        $key = $this->getEffectiveEncryptionKey();
        
        try {
            $algorithm = $this->securityConfig ? $this->securityConfig->getEncryptionAlgorithm() : 'aes-256-cbc';
            $iv = random_bytes(openssl_cipher_iv_length($algorithm));
            $encryptedData = openssl_encrypt($data, $algorithm, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($encryptedData === false) {
                throw new CacheException('Encryption failed');
            }
            
            return base64_encode($iv . $encryptedData);
        } catch (Exception $e) {
            throw new CacheException('Encryption error: ' . $e->getMessage());
        }
    }
    
    /**
     * Gets the effective encryption key to use.
     * 
     * @return string The encryption key
     * @throws CacheException If no valid key is available
     */
    private function getEffectiveEncryptionKey(): string {
        // Use provided key if available and valid
        if (!empty($this->secretKey) && $this->isValidEncryptionKey($this->secretKey)) {
            return hex2bin($this->secretKey);
        }
        
        // Fall back to KeyManager
        try {
            $encKey = KeyManager::getEncryptionKey();
            return hex2bin($encKey);
        } catch (Exception $e) {
            throw new CacheException('No valid encryption key available: ' . $e->getMessage());
        }
    }
}

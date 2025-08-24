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

use RuntimeException;
use InvalidArgumentException;

/**
 * A class for managing encryption keys securely.
 */
class KeyManager {
    private static $key = null;
    
    /**
     * Gets the encryption key for cache operations.
     * 
     * @return string A valid 64-character hexadecimal encryption key
     * @throws RuntimeException If no valid encryption key is available
     */
    public static function getEncryptionKey(): string {
        if (self::$key === null) {
            self::$key = self::loadKey();
        }
        return self::$key;
    }
    
    /**
     * Sets a custom encryption key.
     * 
     * @param string $key A 64-character hexadecimal string
     * @throws InvalidArgumentException If the key is invalid
     */
    public static function setEncryptionKey(string $key): void {
        if (!self::isValidKey($key)) {
            throw new InvalidArgumentException('Invalid encryption key. Must be 64 hexadecimal characters.');
        }
        self::$key = $key;
    }
    
    /**
     * Generates a new cryptographically secure encryption key.
     * 
     * @return string A 64-character hexadecimal encryption key
     */
    public static function generateKey(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Clears the cached key (useful for testing).
     */
    public static function clearCache(): void {
        self::$key = null;
    }
    
    /**
     * Loads encryption key from environment variables only.
     * 
     * @return string A valid encryption key
     * @throws RuntimeException If no valid key can be loaded
     */
    private static function loadKey(): string {
        // Load from environment variable only
        $key = $_ENV['CACHE_ENCRYPTION_KEY'] ?? getenv('CACHE_ENCRYPTION_KEY');
               
        if ($key && self::isValidKey($key)) {
            return $key;
        }
        
        throw new RuntimeException('No valid encryption key found. Please set CACHE_ENCRYPTION_KEY environment variable with a 64-character hexadecimal key.');
    }
    
    /**
     * Validates if a key is properly formatted.
     * 
     * @param string $key The key to validate
     * @return bool True if valid, false otherwise
     */
    private static function isValidKey(string $key): bool {
        return strlen($key) === 64 && ctype_xdigit($key);
    }
}

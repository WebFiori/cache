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

use WebFiori\Cache\Exceptions\InvalidCacheKeyException;

/**
 * A class for managing encryption keys securely.
 */
class KeyManager {
    private static $key = null;

    /**
     * Clears the cached key (useful for testing).
     */
    public static function clearCache(): void {
        self::$key = null;
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
     * Gets the encryption key for cache operations.
     * 
     * @return string|null A valid 64-character hexadecimal encryption key, or null if not available
     */
    public static function getEncryptionKey(): ?string {
        if (self::$key === null) {
            self::$key = self::loadKey();
        }

        return self::$key;
    }

    /**
     * Sets a custom encryption key.
     * 
     * @param string $key A 64-character hexadecimal string
     * @throws InvalidCacheKeyException If the key is invalid
     */
    public static function setEncryptionKey(string $key): void {
        if (!self::isValidKey($key)) {
            throw new InvalidCacheKeyException($key, 'Invalid encryption key format. Must be 64 hexadecimal characters.');
        }
        self::$key = $key;
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

    /**
     * Loads encryption key from environment variables only.
     * 
     * @return string|null A valid encryption key or null if not available
     */
    private static function loadKey(): ?string {
        // Load from environment variable
        $key = $_ENV['CACHE_KEY'] ?? getenv('CACHE_KEY');

        if (!$key) {
            // Fallback to legacy env var for backward compatibility
            $key = $_ENV['CACHE_ENCRYPTION_KEY'] ?? getenv('CACHE_ENCRYPTION_KEY');
        }

        if ($key && self::isValidKey($key)) {
            return $key;
        }

        return null;
    }
}

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

/**
 * Configuration class for cache security settings.
 */
class SecurityConfig {
    private int $directoryPermissions;
    private string $encryptionAlgorithm;
    private bool $encryptionEnabled;
    private int $filePermissions;

    /**
     * Creates new instance with default or environment-based configuration.
     */
    public function __construct() {
        $this->encryptionEnabled = filter_var(
            $_ENV['CACHE_ENCRYPTION_ENABLED'] ?? getenv('CACHE_ENCRYPTION_ENABLED') ?: 'true',
            FILTER_VALIDATE_BOOLEAN
        );
        $this->encryptionAlgorithm = $_ENV['CACHE_ENCRYPTION_ALGORITHM'] ?? 
                                   getenv('CACHE_ENCRYPTION_ALGORITHM') ?: 'aes-256-cbc';
        $this->filePermissions = octdec($_ENV['CACHE_FILE_PERMISSIONS'] ?? 
                                      getenv('CACHE_FILE_PERMISSIONS') ?: '600');
        $this->directoryPermissions = octdec($_ENV['CACHE_DIR_PERMISSIONS'] ?? 
                                           getenv('CACHE_DIR_PERMISSIONS') ?: '700');
    }

    /**
     * Gets directory permissions for cache directories.
     * 
     * @return int Directory permissions as octal integer
     */
    public function getDirectoryPermissions(): int {
        return $this->directoryPermissions;
    }

    /**
     * Gets the encryption algorithm.
     * 
     * @return string The encryption algorithm name
     */
    public function getEncryptionAlgorithm(): string {
        return $this->encryptionAlgorithm;
    }

    /**
     * Gets file permissions for cache files.
     * 
     * @return int File permissions as octal integer
     */
    public function getFilePermissions(): int {
        return $this->filePermissions;
    }

    /**
     * Checks if encryption is enabled.
     * 
     * @return bool True if encryption is enabled
     */
    public function isEncryptionEnabled(): bool {
        return $this->encryptionEnabled;
    }

    /**
     * Sets directory permissions for cache directories.
     * 
     * @param int $permissions Directory permissions as octal integer
     */
    public function setDirectoryPermissions(int $permissions): void {
        $this->directoryPermissions = $permissions;
    }

    /**
     * Sets the encryption algorithm.
     * 
     * @param string $algorithm The encryption algorithm name
     */
    public function setEncryptionAlgorithm(string $algorithm): void {
        $this->encryptionAlgorithm = $algorithm;
    }

    /**
     * Sets encryption enabled state.
     * 
     * @param bool $enabled Whether encryption should be enabled
     */
    public function setEncryptionEnabled(bool $enabled): void {
        $this->encryptionEnabled = $enabled;
    }

    /**
     * Sets file permissions for cache files.
     * 
     * @param int $permissions File permissions as octal integer
     */
    public function setFilePermissions(int $permissions): void {
        $this->filePermissions = $permissions;
    }
}

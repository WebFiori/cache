<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\SecurityConfig;

// 1. Default configuration
$config = new SecurityConfig();
echo "Defaults:\n";
echo "  Encryption enabled:    ".($config->isEncryptionEnabled() ? 'true' : 'false')."\n";
echo "  Algorithm:             ".$config->getEncryptionAlgorithm()."\n";
echo "  File permissions:      ".decoct($config->getFilePermissions())."\n";
echo "  Directory permissions: ".decoct($config->getDirectoryPermissions())."\n\n";

// 2. Custom configuration
$config->setEncryptionEnabled(false);
$config->setEncryptionAlgorithm('aes-128-cbc');
$config->setFilePermissions(0644);
$config->setDirectoryPermissions(0755);

echo "After customization:\n";
echo "  Encryption enabled:    ".($config->isEncryptionEnabled() ? 'true' : 'false')."\n";
echo "  Algorithm:             ".$config->getEncryptionAlgorithm()."\n";
echo "  File permissions:      ".decoct($config->getFilePermissions())."\n";
echo "  Directory permissions: ".decoct($config->getDirectoryPermissions())."\n\n";

// 3. Configuration from environment variables
$_ENV['CACHE_ENCRYPTION_ENABLED'] = 'false';
$_ENV['CACHE_ENCRYPTION_ALGORITHM'] = 'aes-256-gcm';
$_ENV['CACHE_FILE_PERMISSIONS'] = '640';
$_ENV['CACHE_DIR_PERMISSIONS'] = '750';

$envConfig = new SecurityConfig();
echo "From environment variables:\n";
echo "  Encryption enabled:    ".($envConfig->isEncryptionEnabled() ? 'true' : 'false')."\n";
echo "  Algorithm:             ".$envConfig->getEncryptionAlgorithm()."\n";
echo "  File permissions:      ".decoct($envConfig->getFilePermissions())."\n";
echo "  Directory permissions: ".decoct($envConfig->getDirectoryPermissions())."\n";

// Clean up env
unset($_ENV['CACHE_ENCRYPTION_ENABLED']);
unset($_ENV['CACHE_ENCRYPTION_ALGORITHM']);
unset($_ENV['CACHE_FILE_PERMISSIONS']);
unset($_ENV['CACHE_DIR_PERMISSIONS']);

<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\KeyManager;
use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;

// 1. Generate a key
$key = KeyManager::generateKey();
echo "Generated key: $key\n";
echo "Key length:    " . strlen($key) . " characters\n\n";

// 2. Set it programmatically
KeyManager::setEncryptionKey($key);
echo "Key set via setEncryptionKey().\n";
echo "Retrieved key: " . KeyManager::getEncryptionKey() . "\n\n";

// 3. Set via environment variable
KeyManager::clearCache();
$_ENV['CACHE_ENCRYPTION_KEY'] = $key;
echo "Key set via \$_ENV['CACHE_ENCRYPTION_KEY'].\n";
echo "Retrieved key: " . KeyManager::getEncryptionKey() . "\n\n";

// 4. Invalid key is rejected
try {
    KeyManager::setEncryptionKey('too-short');
} catch (InvalidCacheKeyException $e) {
    echo "Invalid key rejected: " . $e->getMessage() . "\n\n";
}

// 5. Missing key throws exception
KeyManager::clearCache();
unset($_ENV['CACHE_ENCRYPTION_KEY']);
try {
    KeyManager::getEncryptionKey();
} catch (CacheException $e) {
    echo "Missing key error: " . $e->getMessage() . "\n";
}

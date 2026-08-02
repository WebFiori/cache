<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Exceptions\InvalidCacheKeyException;
use WebFiori\Cache\KeyManager;

// 1. Generate a key
$key = KeyManager::generateKey();
echo "Generated key: $key\n";
echo "Key length:    ".strlen($key)." characters\n\n";

// 2. Set it programmatically
KeyManager::setEncryptionKey($key);
echo "Key set via setEncryptionKey().\n";
echo "Retrieved key: ".KeyManager::getEncryptionKey()."\n\n";

// 3. Set via environment variable
KeyManager::clearCache();
$_ENV['CACHE_KEY'] = $key;
echo "Key set via \$_ENV['CACHE_KEY'].\n";
echo "Retrieved key: ".KeyManager::getEncryptionKey()."\n\n";

// 4. Invalid key is rejected
try {
    KeyManager::setEncryptionKey('too-short');
} catch (InvalidCacheKeyException $e) {
    echo "Invalid key rejected: ".$e->getMessage()."\n\n";
}

// 5. Missing key returns null
KeyManager::clearCache();
unset($_ENV['CACHE_KEY']);
$result = KeyManager::getEncryptionKey();
echo "Missing key result: ".($result === null ? 'null (encryption disabled)' : $result)."\n";

<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\CacheStorageException;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

// 1. Empty cache key
echo "1. Empty cache key:\n";
try {
    $cache->set('', 'data');
} catch (InvalidCacheKeyException $e) {
    echo "   ".$e->getMessage()."\n\n";
}

// 2. Key too long (>250 characters)
echo "2. Key too long:\n";
try {
    $cache->set(str_repeat('x', 251), 'data');
} catch (InvalidCacheKeyException $e) {
    echo "   ".$e->getMessage()."\n\n";
}

// 3. Key with control characters
echo "3. Key with control characters:\n";
try {
    $cache->set("bad\x00key", 'data');
} catch (InvalidCacheKeyException $e) {
    echo "   ".$e->getMessage()."\n\n";
}

// 4. Invalid storage path (empty)
echo "4. Empty storage path:\n";
try {
    new FileStorage('');
} catch (CacheStorageException $e) {
    echo "   ".$e->getMessage()."\n\n";
}

// 5. Storage path is a file, not a directory
echo "5. Storage path is a file:\n";
$tempFile = __DIR__.'/not_a_dir';
file_put_contents($tempFile, 'test');
try {
    new FileStorage($tempFile);
} catch (CacheStorageException $e) {
    echo "   ".$e->getMessage()."\n\n";
} finally {
    unlink($tempFile);
}

// 6. Missing encryption key when encryption is enabled
echo "6. Missing encryption key:\n";
KeyManager::clearCache();
unset($_ENV['CACHE_ENCRYPTION_KEY']);
try {
    KeyManager::getEncryptionKey();
} catch (CacheException $e) {
    echo "   ".$e->getMessage()."\n";
}

$cache->flush();

if (is_dir(__DIR__.'/cache')) {
    rmdir(__DIR__.'/cache');
}

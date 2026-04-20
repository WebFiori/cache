<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cacheDir = __DIR__.'/factory_cache';

// Create a cache instance directly with DI
$cache = new Cache(new FileStorage($cacheDir), true, 'myapp_');

echo "Instance type: ".get_class($cache)."\n";
echo "Prefix: ".$cache->getPrefix()."\n";

$cache->set('setting', 'dark_mode', 60);
echo "Stored 'setting': ".$cache->get('setting')."\n";

// Clean up
$cache->flush();
rmdir($cacheDir);
echo "Cleaned up.\n";

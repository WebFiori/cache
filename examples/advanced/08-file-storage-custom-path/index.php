<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$customPath = __DIR__.'/my_cache';

// Create a Cache with a custom directory
$cache = new Cache(new FileStorage($customPath));

echo "Cache directory: ".$cache->getDriver()->getPath()."\n";

$cache->set('example', 'stored in custom path', 60);
echo "Stored value: ".$cache->get('example')."\n";

$files = glob($customPath.'/*.cache');
echo "Cache files found: ".count($files)."\n";

$cache->flush();
rmdir($customPath);
echo "Cleaned up.\n";

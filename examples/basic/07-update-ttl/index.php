<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$cache->set('session', 'data', 60);

$item = $cache->getItem('session');
echo "Original TTL: ".$item->getTTL()." seconds\n";

$cache->setTTL('session', 3600);

$item = $cache->getItem('session');
echo "Updated TTL:  ".$item->getTTL()." seconds\n";

// setTTL returns false for non-existent keys
$result = $cache->setTTL('no_such_key', 100);
echo "setTTL on missing key: ".($result ? 'true' : 'false')."\n";

$cache->flush();
rmdir(__DIR__.'/cache');

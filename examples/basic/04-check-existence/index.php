<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

echo "Before set - has 'status': ".($cache->has('status') ? 'true' : 'false')."\n";

$cache->set('status', 'active', 60);

echo "After set  - has 'status': ".($cache->has('status') ? 'true' : 'false')."\n";
echo "Has 'other_key': ".($cache->has('other_key') ? 'true' : 'false')."\n";

$cache->flush();
rmdir(__DIR__.'/cache');

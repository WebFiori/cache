<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$cache->set('config', 'original', 60);
echo "Initial value: ".$cache->get('config')."\n";

// Without override: set() returns false, value unchanged
$result = $cache->set('config', 'new_value', 60, false);
echo "set() without override returned: ".($result ? 'true' : 'false')."\n";
echo "Value after: ".$cache->get('config')."\n";

// With override: set() returns true, value updated
$result = $cache->set('config', 'new_value', 60, true);
echo "set() with override returned: ".($result ? 'true' : 'false')."\n";
echo "Value after: ".$cache->get('config')."\n";

$cache->flush();
rmdir(__DIR__.'/cache');

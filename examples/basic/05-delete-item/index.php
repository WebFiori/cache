<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$cache->set('temp_data', 'will be deleted', 60);
echo "Before delete - has 'temp_data': ".($cache->has('temp_data') ? 'true' : 'false')."\n";

$cache->delete('temp_data');
echo "After delete  - has 'temp_data': ".($cache->has('temp_data') ? 'true' : 'false')."\n";

// Deleting a non-existent key does not throw an error
$cache->delete('does_not_exist');
echo "Deleting non-existent key: no error.\n";

$cache->flush();
rmdir(__DIR__.'/cache');

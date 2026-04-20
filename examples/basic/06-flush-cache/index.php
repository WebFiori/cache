<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

$cache->set('item_a', 'A', 60);
$cache->set('item_b', 'B', 60);
$cache->set('item_c', 'C', 60);

echo "Before flush:\n";
echo "  has 'item_a': ".($cache->has('item_a') ? 'true' : 'false')."\n";
echo "  has 'item_b': ".($cache->has('item_b') ? 'true' : 'false')."\n";
echo "  has 'item_c': ".($cache->has('item_c') ? 'true' : 'false')."\n";

$cache->flush();

echo "After flush:\n";
echo "  has 'item_a': ".($cache->has('item_a') ? 'true' : 'false')."\n";
echo "  has 'item_b': ".($cache->has('item_b') ? 'true' : 'false')."\n";
echo "  has 'item_c': ".($cache->has('item_c') ? 'true' : 'false')."\n";

rmdir(__DIR__.'/cache');

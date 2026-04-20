<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

// Store items under different prefixes
$users = $cache->withPrefix('users_');
$orders = $cache->withPrefix('orders_');

$users->set('count', 100, 60);
$orders->set('count', 250, 60);

// Each prefix has its own isolated namespace
echo "users_count:  ".$users->get('count')."\n";
echo "orders_count: ".$orders->get('count')."\n";

// Without prefix, the key does not exist
echo "no prefix 'count': ".var_export($cache->get('count'), true)."\n";

// Clean up
$users->flush();
$orders->flush();
rmdir(__DIR__.'/cache');

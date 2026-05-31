<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\RedisStorage;

// Optional: set encryption key
$_ENV['CACHE_KEY'] = KeyManager::generateKey();

// Connect to Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Create cache with Redis backend
$storage = new RedisStorage($redis, 'wf_cache:');
$cache = new Cache($storage);

// Basic set and get
$cache->set('greeting', 'Hello from Redis!', 60);
echo $cache->get('greeting')."\n"; // Hello from Redis!

// Prefix isolation
$users = $cache->withPrefix('users_');
$orders = $cache->withPrefix('orders_');

$users->set('count', 42, 60);
$orders->set('count', 100, 60);

echo "Users: ".$users->get('count')."\n";   // 42
echo "Orders: ".$orders->get('count')."\n"; // 100

// Generator callback
$data = $cache->get('computed', function () {
    return ['result' => 'expensive computation'];
}, 120);

echo "Computed: ".print_r($data, true)."\n";

// Cleanup
$cache->flush();
echo "Done.\n";

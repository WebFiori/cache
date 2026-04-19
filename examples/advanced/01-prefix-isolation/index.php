<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store items under different prefixes
Cache::withPrefix('users_')->set('count', 100, 60);
Cache::withPrefix('orders_')->set('count', 250, 60);

// Each prefix has its own isolated namespace
echo "users_count:  " . Cache::withPrefix('users_')->get('count') . "\n";
echo "orders_count: " . Cache::withPrefix('orders_')->get('count') . "\n";

// Without prefix, the key does not exist
echo "no prefix 'count': " . var_export(Cache::withPrefix('')->get('count'), true) . "\n";

// Clean up
Cache::withPrefix('users_')->flush();
Cache::withPrefix('orders_')->flush();

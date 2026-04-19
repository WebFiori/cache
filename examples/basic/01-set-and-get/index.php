<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

// Set up encryption key
$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store a value
Cache::set('greeting', 'Hello, World!', 60);
echo "Stored 'greeting' in cache.\n";

// Retrieve the value
$data = Cache::get('greeting');
echo "Retrieved: $data\n";

// Attempt to retrieve a non-existent key
$missing = Cache::get('non_existent');
echo "Non-existent key returns: " . var_export($missing, true) . "\n";

// Clean up
Cache::flush();

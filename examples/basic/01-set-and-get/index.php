<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cache = new Cache(new FileStorage(__DIR__.'/cache'));

// Store a value
$cache->set('greeting', 'Hello, World!', 60);
echo "Stored 'greeting' in cache.\n";

// Retrieve the value
$data = $cache->get('greeting');
echo "Retrieved: $data\n";

// Attempt to retrieve a non-existent key
$missing = $cache->get('non_existent');
echo "Non-existent key returns: ".var_export($missing, true)."\n";

// Clean up
$cache->flush();
rmdir(__DIR__.'/cache');

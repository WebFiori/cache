<?php
require_once __DIR__ . '/../../../vendor/autoload.php';

use WebFiori\Cache\Cache;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

// Store items under two prefixes
Cache::withPrefix('app1_')->set('data', 'App 1 data', 60);
Cache::withPrefix('app1_')->set('config', 'App 1 config', 60);
Cache::withPrefix('app2_')->set('data', 'App 2 data', 60);
Cache::withPrefix('app2_')->set('config', 'App 2 config', 60);

echo "Before flush:\n";
echo "  app1_data:   " . Cache::withPrefix('app1_')->get('data') . "\n";
echo "  app1_config: " . Cache::withPrefix('app1_')->get('config') . "\n";
echo "  app2_data:   " . Cache::withPrefix('app2_')->get('data') . "\n";
echo "  app2_config: " . Cache::withPrefix('app2_')->get('config') . "\n";

// Flush only app1_ items
Cache::withPrefix('app1_')->flush();

echo "\nAfter flushing 'app1_':\n";
echo "  app1_data:   " . var_export(Cache::withPrefix('app1_')->get('data'), true) . "\n";
echo "  app1_config: " . var_export(Cache::withPrefix('app1_')->get('config'), true) . "\n";
echo "  app2_data:   " . Cache::withPrefix('app2_')->get('data') . "\n";
echo "  app2_config: " . Cache::withPrefix('app2_')->get('config') . "\n";

Cache::withPrefix('app2_')->flush();

<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\FileStorage;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$cacheDir = __DIR__.'/perm_cache';

// 1. Default permissions (0600 file, 0700 directory)
$storage = new FileStorage($cacheDir);
$item = new Item('default_perms', 'data', 60, KeyManager::getEncryptionKey());
$storage->store($item);

$dirPerms = decoct(fileperms($cacheDir) & 0777);
$files = glob($cacheDir.'/*.cache');
$filePerms = decoct(fileperms($files[0]) & 0777);

echo "Default permissions:\n";
echo "  Directory: $dirPerms\n";
echo "  File:      $filePerms\n\n";

// Clean up first set
$storage->flush(null);
rmdir($cacheDir);

// 2. Custom permissions (0644 file, 0755 directory)
$storage2 = new FileStorage($cacheDir);
$config = new SecurityConfig();
$config->setFilePermissions(0644);
$config->setDirectoryPermissions(0755);

$item2 = new Item('custom_perms', 'data', 60, KeyManager::getEncryptionKey());
$item2->setSecurityConfig($config);
$storage2->store($item2);

$dirPerms2 = decoct(fileperms($cacheDir) & 0777);
$files2 = glob($cacheDir.'/*.cache');
$filePerms2 = decoct(fileperms($files2[0]) & 0777);

echo "Custom permissions:\n";
echo "  Directory: $dirPerms2\n";
echo "  File:      $filePerms2\n";

// Clean up
$storage2->flush(null);
rmdir($cacheDir);

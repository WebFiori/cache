<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Item;
use WebFiori\Cache\SecurityConfig;

// Create an item with encryption disabled
$item = new Item('public_data', 'This is not secret', 60, '');

$config = new SecurityConfig();
$config->setEncryptionEnabled(false);
$item->setSecurityConfig($config);

// getDataEncrypted() returns serialized (but NOT encrypted) data
$stored = $item->getDataEncrypted();
echo "Stored representation: $stored\n";
echo "Is it just serialized? ".(unserialize($stored) === 'This is not secret' ? 'yes' : 'no')."\n";

// getDataDecrypted() returns the original value
$original = $item->getDataDecrypted();
echo "Decrypted value:       $original\n";

<?php

require_once __DIR__.'/../../../vendor/autoload.php';

use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;

$_ENV['CACHE_ENCRYPTION_KEY'] = KeyManager::generateKey();

$secret = KeyManager::getEncryptionKey();
$original = 'Sensitive payment info: card ending 1234';

// Create item and encrypt
$item = new Item('payment', $original, 60, $secret);
$encrypted = $item->getDataEncrypted();

echo "Original:  $original\n";
echo "Encrypted: $encrypted\n";
echo "Match?     ".($encrypted === $original ? 'same (BAD)' : 'different (encrypted)')."\n\n";

// Simulate reading from storage: create new item with encrypted data
$fromStorage = new Item('payment', $encrypted, 60, $secret);
$fromStorage->setDataIsEncrypted(true);
$fromStorage->setDataFromStorage(true);

$decrypted = $fromStorage->getDataDecrypted();
echo "Decrypted: $decrypted\n";
echo "Round-trip match? ".($decrypted === $original ? 'yes' : 'no')."\n";

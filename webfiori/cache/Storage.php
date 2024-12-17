<?php
/**
 * This file is licensed under MIT License.
 *
 * Copyright (c) 2024 Ibrahim BinAlshikh and Contributors
 *
 * For more information on the license, please visit:
 * https://github.com/WebFiori/.github/blob/main/LICENSE
 *
 */
namespace webfiori\cache;

use Override;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * An interface that has the method which must be implemented by any cache engine.
 */
abstract class Storage implements CacheItemPoolInterface {
    private $deferredItems;
    
    public function __construct() {
        $this->clearDeferredItems();
    }
    #[Override]
    public function saveDeferred(CacheItemInterface $item): bool {
        $this->deferredItems[$item->getKey()] = $item;
        return true;
    }
    #[Override]
    public function commit(): bool {
        $saved = true;
        foreach ($this->deferredItems as $item) {
            if ($this->save($item)) {
                unset($this->deferredItems[$item->getKey()]);
            } else {
                $saved = false;
            }
            
        }
        return $saved;
    }
    public function clearDeferredItems() {
        $this->deferredItems = [];
    }
    public function hasDeferred(string $key) : bool {
        return isset($this->deferredItems[$key]);
    }
    public function deleteDeferredItem(string $key) {
        unset($this->deferredItems[$key]);
    }
    public function deleteDeferredItems(array $keys) {
        foreach ($keys as $key) {
            $this->deleteDeferredItem($key);
        }
    }
    public function getDeferredItem(string $key) : ?CacheItemInterface {
        if (!isset($this->deferredItems[$key])) {
            return null;
        }
        $item = $this->deferredItems[$key];
        $item instanceof Item;
        if ($item->getExpiryTime() < time()) {
            $this->deleteDeferredItem($key);
            return null;
        }
        return $item;
    }
}

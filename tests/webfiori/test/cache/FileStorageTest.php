<?php

namespace webfiori\test\cache;

use PHPUnit\Framework\TestCase;
use webfiori\cache\FileStorage;
use webfiori\cache\Item;
/**
 */
class FileStorageTest extends TestCase {
    /**
     * @test
     */
    public function test00() {
        $fileStorage = new FileStorage(__DIR__);
        $this->assertEquals(__DIR__, $fileStorage->getPath());
    }
    /**
     * @test
     */
    public function testSave00() {
        $fileStorage = new FileStorage(__DIR__);
        $key = 'item_'. random_bytes(6);
        $this->assertFalse($fileStorage->hasItem($key));
        $item = new Item($key, 'Data', 60);
        $this->assertFalse($item->isHit());
        $fileStorage->save($item);
        $this->assertTrue($fileStorage->hasItem($key));
        $itemFromCache = $fileStorage->getItem($key);
        $this->assertEquals("Data", $itemFromCache->get());
        $this->assertEquals(60, $itemFromCache->getTTL());
        $this->assertEquals($key, $itemFromCache->getKey());
        $this->assertTrue($itemFromCache->isHit());
    }
    /**
     * @test
     */
    public function testDelete00() {
        $fileStorage = new FileStorage(__DIR__);
        $key = 'item_'. random_bytes(8);
        $this->assertFalse($fileStorage->hasItem($key));
        $this->assertNull($fileStorage->read($key));
        $this->assertNotNull($fileStorage->getItem($key));
        $item = new Item($key, 'Data', 60);
        $fileStorage->save($item);
        $this->assertTrue($fileStorage->hasItem($key));
        $this->assertEquals('Data',$fileStorage->read($key));
        $this->assertNotNull($fileStorage->getItem($key));
        $fileStorage->deleteItem($key);
        $this->assertFalse($fileStorage->hasItem($key));
        $this->assertNull($fileStorage->read($key));
        $item = $fileStorage->getItem($key);
        $this->assertEquals(-1, $item->getTTL());
    }
}

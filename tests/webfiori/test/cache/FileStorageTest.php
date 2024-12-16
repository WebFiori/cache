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
        $this->assertFalse($fileStorage->has($key));
        $item = new Item($key, 'Data', 60);
        $this->assertFalse($item->isHit());
        $fileStorage->store($item);
        $this->assertTrue($fileStorage->has($key));
        $itemFromCache = $fileStorage->readItem($key);
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
        $this->assertFalse($fileStorage->has($key));
        $this->assertNull($fileStorage->read($key));
        $this->assertNull($fileStorage->readItem($key));
        $item = new Item($key, 'Data', 60);
        $fileStorage->store($item);
        $this->assertTrue($fileStorage->has($key));
        $this->assertEquals('Data',$fileStorage->read($key));
        $this->assertNotNull($fileStorage->readItem($key));
        $fileStorage->delete($key);
        $this->assertFalse($fileStorage->has($key));
        $this->assertNull($fileStorage->read($key));
        $this->assertNull($fileStorage->readItem($key));
    }
}

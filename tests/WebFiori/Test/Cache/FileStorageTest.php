<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\FileStorage;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;
use WebFiori\Cache\Exceptions\CacheStorageException;

/**
 * Comprehensive test class for FileStorage functionality.
 */
class FileStorageTest extends TestCase {
    
    private string $testDir;
    
    protected function setUp(): void {
        // Set up a test encryption key for consistent testing
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache();
        
        $this->testDir = __DIR__ . '/test_file_storage_' . uniqid();
    }
    
    protected function tearDown(): void {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $files = glob($this->testDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDir);
        }
        
        KeyManager::clearCache();
        unset($_ENV['CACHE_ENCRYPTION_KEY']);
    }
    
    /**
     * @test
     */
    public function testFileStorageCreation() {
        $storage = new FileStorage($this->testDir);
        $this->assertEquals($this->testDir, $storage->getPath());
        // Directory will be created when first item is stored
    }
    
    /**
     * @test
     */
    public function testFileStorageSetPath() {
        $storage = new FileStorage($this->testDir);
        
        $newPath = $this->testDir . '_new';
        $storage->setPath($newPath);
        $this->assertEquals($newPath, $storage->getPath());
        
        // Clean up new directory
        if (is_dir($newPath)) {
            rmdir($newPath);
        }
    }
    
    /**
     * @test
     */
    public function testStoreAndRetrieveItem() {
        $storage = new FileStorage($this->testDir);
        $key = 'test_key';
        $data = 'test_data';
        $ttl = 300;
        
        // Use the same key that's set in KeyManager, not a new random key
        $item = new Item($key, $data, $ttl, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Check directory was created
        $this->assertTrue(is_dir($this->testDir));
        
        // Check file was created
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(1, $files);
        
        // Test has method
        $this->assertTrue($storage->has($key, ''));
        
        // Test read method
        $retrievedData = $storage->read($key, '');
        $this->assertEquals($data, $retrievedData);
        
        // Test readItem method
        $retrievedItem = $storage->readItem($key, '');
        $this->assertInstanceOf(Item::class, $retrievedItem);
        $this->assertEquals($key, $retrievedItem->getKey());
        $this->assertEquals($data, $retrievedItem->getDataDecrypted());
        $this->assertEquals($ttl, $retrievedItem->getTTL());
    }
    
    /**
     * @test
     */
    public function testStoreItemWithZeroTTL() {
        $storage = new FileStorage($this->testDir);
        $key = 'zero_ttl_key';
        $data = 'zero_ttl_data';
        
        $item = new Item($key, $data, 0, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // No file should be created for zero TTL
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(0, $files);
        
        $this->assertFalse($storage->has($key, ''));
        $this->assertNull($storage->read($key, ''));
    }
    
    /**
     * @test
     */
    public function testDeleteItem() {
        $storage = new FileStorage($this->testDir);
        $key = 'delete_test_key';
        $data = 'delete_test_data';
        
        $item = new Item($key, $data, 300, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Verify directory and file were created
        $this->assertTrue(is_dir($this->testDir));
        $this->assertTrue($storage->has($key, ''));
        
        $storage->delete($key);
        
        $this->assertFalse($storage->has($key, ''));
        $this->assertNull($storage->read($key, ''));
        
        // File should be deleted
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(0, $files);
    }
    
    /**
     * @test
     */
    public function testDeleteNonExistentItem() {
        $storage = new FileStorage($this->testDir);
        
        // Should not throw exception
        $storage->delete('non_existent_key');
        $this->assertTrue(true); // Test passes if no exception is thrown
    }
    
    /**
     * @test
     */
    public function testFlushAllItems() {
        $storage = new FileStorage($this->testDir);
        
        // Store multiple items
        for ($i = 0; $i < 5; $i++) {
            $item = new Item("key_{$i}", "data_{$i}", 300, KeyManager::getEncryptionKey());
            $storage->store($item);
        }
        
        // Verify items exist
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(5, $files);
        
        // Flush all
        $storage->flush(null);
        
        // Verify all items are deleted
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(0, $files);
    }
    
    /**
     * @test
     */
    public function testFlushWithPrefix() {
        $storage = new FileStorage($this->testDir);
        $prefix1 = 'prefix1_';
        $prefix2 = 'prefix2_';
        
        // Store items with different prefixes
        for ($i = 0; $i < 3; $i++) {
            $item1 = new Item("key_{$i}", "data1_{$i}", 300, KeyManager::getEncryptionKey());
            $item1->setPrefix($prefix1);
            $storage->store($item1);
            
            $item2 = new Item("key_{$i}", "data2_{$i}", 300, KeyManager::getEncryptionKey());
            $item2->setPrefix($prefix2);
            $storage->store($item2);
        }
        
        // Verify directory was created and all items exist
        $this->assertTrue(is_dir($this->testDir));
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(6, $files);
        
        // Flush only prefix1 items
        $storage->flush($prefix1);
        
        // Verify only prefix2 items remain
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(3, $files);
        
        // Verify prefix1 items are gone
        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($storage->has("key_{$i}", $prefix1));
            $this->assertTrue($storage->has("key_{$i}", $prefix2));
        }
    }
    
    /**
     * @test
     */
    public function testExpiredItemHandling() {
        $storage = new FileStorage($this->testDir);
        $key = 'expired_key';
        $data = 'expired_data';
        
        // Create item with 1 second TTL
        $item = new Item($key, $data, 1, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Verify directory and item were created
        $this->assertTrue(is_dir($this->testDir));
        $this->assertTrue($storage->has($key, ''));
        $this->assertEquals($data, $storage->read($key, ''));
        
        // Wait for expiration
        sleep(2);
        
        // Item should be considered expired and deleted
        $this->assertFalse($storage->has($key, ''));
        $this->assertNull($storage->read($key, ''));
        $this->assertNull($storage->readItem($key, ''));
        
        // File should be deleted
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(0, $files);
    }
    
    /**
     * @test
     */
    public function testCorruptedFileHandling() {
        $storage = new FileStorage($this->testDir);
        $key = 'corrupted_key';
        
        // Create a corrupted cache file
        $fileName = md5($key) . '.cache';
        $filePath = $this->testDir . '/' . $fileName;
        
        if (!is_dir($this->testDir)) {
            mkdir($this->testDir, 0700, true);
        }
        
        file_put_contents($filePath, 'corrupted_data_not_serializable');
        
        $this->expectException(CacheStorageException::class);
        $this->expectExceptionMessage('Failed to unserialize cache data from:');
        
        $storage->readItem($key, '');
    }
    
    /**
     * @test
     */
    public function testFilePermissions() {
        $storage = new FileStorage($this->testDir);
        $key = 'permissions_test';
        $data = 'permissions_data';
        
        $item = new Item($key, $data, 300, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Check directory permissions
        $dirPerms = fileperms($this->testDir) & 0777;
        $this->assertEquals(0700, $dirPerms);
        
        // Check file permissions
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(1, $files);
        
        $filePerms = fileperms($files[0]) & 0777;
        $this->assertEquals(0600, $filePerms);
    }
    
    /**
     * @test
     */
    public function testCustomFilePermissions() {
        $storage = new FileStorage($this->testDir);
        $key = 'custom_permissions_test';
        $data = 'custom_permissions_data';
        
        // Create item with custom security config
        $config = new SecurityConfig();
        $config->setFilePermissions(0644);
        $config->setDirectoryPermissions(0755);
        
        $item = new Item($key, $data, 300, KeyManager::getEncryptionKey());
        $item->setSecurityConfig($config);
        $storage->store($item);
        
        // Check directory permissions
        $dirPerms = fileperms($this->testDir) & 0777;
        $this->assertEquals(0755, $dirPerms);
        
        // Check file permissions
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(1, $files);
        
        $filePerms = fileperms($files[0]) & 0777;
        $this->assertEquals(0644, $filePerms);
    }
    
    /**
     * @test
     */
    public function testAtomicWrite() {
        $storage = new FileStorage($this->testDir);
        $key = 'atomic_test';
        $data = 'atomic_data';
        
        $item = new Item($key, $data, 300, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Verify no temporary files remain
        $tempFiles = glob($this->testDir . '/*.tmp');
        $this->assertCount(0, $tempFiles);
        
        // Verify cache file exists
        $cacheFiles = glob($this->testDir . '/*.cache');
        $this->assertCount(1, $cacheFiles);
    }
    
    /**
     * @test
     */
    public function testLargeDataStorage() {
        $storage = new FileStorage($this->testDir);
        $key = 'large_data_test';
        
        // Create smaller data (10KB instead of 1MB to avoid memory issues)
        $largeData = str_repeat('A', 10 * 1024);
        
        $item = new Item($key, $largeData, 300, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Verify directory was created
        $this->assertTrue(is_dir($this->testDir));
        
        $retrievedData = $storage->read($key, '');
        $this->assertEquals($largeData, $retrievedData);
        $this->assertEquals(10 * 1024, strlen($retrievedData));
    }
    
    /**
     * @test
     */
    public function testMultipleItemsWithSameKeyDifferentPrefixes() {
        $storage = new FileStorage($this->testDir);
        $key = 'same_key';
        
        $item1 = new Item($key, 'data1', 300, KeyManager::getEncryptionKey());
        $item1->setPrefix('app1_');
        $storage->store($item1);
        
        $item2 = new Item($key, 'data2', 300, KeyManager::getEncryptionKey());
        $item2->setPrefix('app2_');
        $storage->store($item2);
        
        $item3 = new Item($key, 'data3', 300, KeyManager::getEncryptionKey());
        $item3->setPrefix(''); // No prefix
        $storage->store($item3);
        
        // All items should coexist
        $this->assertEquals('data1', $storage->read($key, 'app1_'));
        $this->assertEquals('data2', $storage->read($key, 'app2_'));
        $this->assertEquals('data3', $storage->read($key, ''));
        
        // Should have 3 different files
        $files = glob($this->testDir . '/*.cache');
        $this->assertCount(3, $files);
    }
    
    /**
     * @test
     */
    public function testStorageWithoutEncryption() {
        // Clear encryption key to test without encryption
        $originalKey = $_ENV['CACHE_ENCRYPTION_KEY'] ?? null;
        unset($_ENV['CACHE_ENCRYPTION_KEY']);
        KeyManager::clearCache();
        
        try {
            $storage = new FileStorage($this->testDir);
            $key = 'no_encryption_test';
            $data = 'no_encryption_data';
            
            $item = new Item($key, $data, 300, '');
            $config = new SecurityConfig();
            $config->setEncryptionEnabled(false);
            $item->setSecurityConfig($config);
            
            $storage->store($item);
            
            $retrievedData = $storage->read($key, '');
            $this->assertEquals($data, $retrievedData);
            
        } finally {
            // Restore original key
            if ($originalKey !== null) {
                $_ENV['CACHE_ENCRYPTION_KEY'] = $originalKey;
                KeyManager::clearCache();
            }
        }
    }
    
    /**
     * @test
     */
    public function testConcurrentAccess() {
        $storage = new FileStorage($this->testDir);
        $key = 'concurrent_test';
        
        // Simulate concurrent writes by storing multiple items quickly
        for ($i = 0; $i < 10; $i++) {
            $item = new Item($key, "data_{$i}", 300, KeyManager::getEncryptionKey());
            $storage->store($item);
        }
        
        // Should have the last written value
        $retrievedData = $storage->read($key, '');
        $this->assertEquals('data_9', $retrievedData);
        
        // Should only have one file for this key
        $files = glob($this->testDir . '/' . md5($key) . '.cache');
        $this->assertCount(1, $files);
    }
}

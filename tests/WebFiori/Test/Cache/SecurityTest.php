<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;
use WebFiori\Cache\FileStorage;
use InvalidArgumentException;
use WebFiori\Cache\Exceptions\CacheException;
use WebFiori\Cache\Exceptions\InvalidCacheKeyException;

/**
 * Test class for security enhancements.
 */
class SecurityTest extends TestCase {
    
    protected function setUp(): void {
        // Set up a test encryption key for consistent testing
        $testKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $testKey;
        KeyManager::clearCache(); // Force reload from environment
        
        // Clean up any existing cache
        Cache::flush();
    }
    
    protected function tearDown(): void {
        // Clean up after each test
        KeyManager::clearCache();
        Cache::flush();
        
        // Don't clear CACHE_ENCRYPTION_KEY here as it affects other tests
        // Only clear the other environment variables that we set in specific tests
        unset($_ENV['CACHE_ENCRYPTION_ENABLED']);
        unset($_ENV['CACHE_ENCRYPTION_ALGORITHM']);
        unset($_ENV['CACHE_FILE_PERMISSIONS']);
        unset($_ENV['CACHE_DIR_PERMISSIONS']);
    }
    
    /**
     * @test
     */
    public function testKeyManagerGeneratesValidKey() {
        $key = KeyManager::generateKey();
        $this->assertEquals(64, strlen($key));
        $this->assertTrue(ctype_xdigit($key));
    }
    
    /**
     * @test
     */
    public function testKeyManagerSetValidKey() {
        $validKey = KeyManager::generateKey();
        KeyManager::setEncryptionKey($validKey);
        $this->assertEquals($validKey, KeyManager::getEncryptionKey());
    }
    
    /**
     * @test
     */
    public function testKeyManagerRejectsInvalidKey() {
        $this->expectException(InvalidCacheKeyException::class);
        $this->expectExceptionMessage('Invalid encryption key format. Must be 64 hexadecimal characters.');
        KeyManager::setEncryptionKey('invalid_key');
    }
    
    /**
     * @test
     */
    public function testKeyManagerRejectsShortKey() {
        $this->expectException(InvalidCacheKeyException::class);
        KeyManager::setEncryptionKey('abc123'); // Too short
    }
    
    /**
     * @test
     */
    public function testKeyManagerRejectsNonHexKey() {
        $this->expectException(InvalidCacheKeyException::class);
        KeyManager::setEncryptionKey(str_repeat('g', 64)); // Invalid hex characters
    }
    
    /**
     * @test
     */
    public function testKeyManagerRequiresEnvironmentVariable() {
        // Store current environment variable
        $originalKey = $_ENV['CACHE_ENCRYPTION_KEY'] ?? null;
        
        try {
            // Clear any existing keys and environment variables
            KeyManager::clearCache();
            unset($_ENV['CACHE_ENCRYPTION_KEY']);
            
            $this->expectException(CacheException::class);
            $this->expectExceptionMessage('No valid encryption key found. Please set CACHE_ENCRYPTION_KEY environment variable');
            
            KeyManager::getEncryptionKey();
        } finally {
            // Restore environment variable
            if ($originalKey !== null) {
                $_ENV['CACHE_ENCRYPTION_KEY'] = $originalKey;
                KeyManager::clearCache(); // Force reload
            }
        }
    }
    
    /**
     * @test
     */
    public function testSecurityConfigDefaults() {
        $config = new SecurityConfig();
        $this->assertTrue($config->isEncryptionEnabled());
        $this->assertEquals('aes-256-cbc', $config->getEncryptionAlgorithm());
        $this->assertEquals(0600, $config->getFilePermissions());
        $this->assertEquals(0700, $config->getDirectoryPermissions());
    }
    
    /**
     * @test
     */
    public function testSecurityConfigCustomization() {
        $config = new SecurityConfig();
        $config->setEncryptionEnabled(false);
        $config->setEncryptionAlgorithm('aes-128-cbc');
        $config->setFilePermissions(0644);
        $config->setDirectoryPermissions(0755);
        
        $this->assertFalse($config->isEncryptionEnabled());
        $this->assertEquals('aes-128-cbc', $config->getEncryptionAlgorithm());
        $this->assertEquals(0644, $config->getFilePermissions());
        $this->assertEquals(0755, $config->getDirectoryPermissions());
    }
    
    /**
     * @test
     */
    public function testItemValidatesEncryptionKey() {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption key provided. Must be 64 hexadecimal characters.');
        new Item('test', 'data', 60, 'invalid_key');
    }
    
    /**
     * @test
     */
    public function testItemAcceptsValidEncryptionKey() {
        $validKey = KeyManager::generateKey();
        $item = new Item('test', 'data', 60, $validKey);
        $this->assertEquals($validKey, $item->getSecret());
    }
    
    /**
     * @test
     */
    public function testItemEncryptionDecryption() {
        $validKey = KeyManager::generateKey();
        $testData = 'This is sensitive test data';
        
        $item = new Item('test', $testData, 60, $validKey);
        
        // Test encryption
        $encrypted = $item->getDataEncrypted();
        $this->assertNotEquals($testData, $encrypted);
        $this->assertTrue(strlen($encrypted) > 0);
        
        // Test that we can get the original data back
        $originalData = $item->getData();
        $this->assertEquals($testData, $originalData);
        
        // Test round-trip through storage simulation
        // Create a new item with the encrypted data (simulating storage retrieval)
        $retrievedItem = new Item('test', $encrypted, 60, $validKey);
        $retrievedItem->setDataIsEncrypted(true);
        
        $decrypted = $retrievedItem->getDataDecrypted();
        $this->assertEquals($testData, $decrypted);
    }
    
    /**
     * @test
     */
    public function testItemEncryptionWithoutKey() {
        // Create item with disabled encryption
        $item = new Item('test', 'data', 60, '');
        $config = new SecurityConfig();
        $config->setEncryptionEnabled(false);
        $item->setSecurityConfig($config);
        
        $encrypted = $item->getDataEncrypted();
        $decrypted = $item->getDataDecrypted();
        
        // Without encryption, data should be serialized but not encrypted
        $this->assertEquals('data', $decrypted);
        // The encrypted data should just be serialized data
        $this->assertEquals(serialize('data'), $encrypted);
    }
    
    /**
     * @test
     */
    public function testItemFailsWithoutValidKeyWhenEncryptionEnabled() {
        // Store current environment variable
        $originalKey = $_ENV['CACHE_ENCRYPTION_KEY'] ?? null;
        
        try {
            // Clear any existing keys and environment variables
            KeyManager::clearCache();
            unset($_ENV['CACHE_ENCRYPTION_KEY']);
            
            $this->expectException(CacheException::class);
            $this->expectExceptionMessage('No valid encryption key available');
            
            $item = new Item('test', 'data', 60, '');
            // This should fail because no key is available and encryption is enabled
            $item->getDataEncrypted();
        } finally {
            // Restore environment variable
            if ($originalKey !== null) {
                $_ENV['CACHE_ENCRYPTION_KEY'] = $originalKey;
                KeyManager::clearCache(); // Force reload
            }
        }
    }
    
    /**
     * @test
     */
    public function testCacheUsesKeyManager() {
        $validKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $validKey;
        KeyManager::clearCache(); // Force reload from environment
        
        $testData = 'Test cache data';
        Cache::set('test_key', $testData, 60);
        
        $retrievedData = Cache::get('test_key');
        $this->assertEquals($testData, $retrievedData);
    }
    
    /**
     * @test
     */
    public function testFileStorageSecurePermissions() {
        $validKey = KeyManager::generateKey();
        KeyManager::setEncryptionKey($validKey);
        
        $testDir = __DIR__ . '/test_cache';
        $storage = new FileStorage($testDir);
        
        $item = new Item('test', 'data', 60, $validKey);
        $storage->store($item);
        
        // Check directory permissions
        $this->assertTrue(is_dir($testDir));
        $dirPerms = fileperms($testDir) & 0777;
        $this->assertEquals(0700, $dirPerms);
        
        // Check file permissions
        $files = glob($testDir . '/*.cache');
        $this->assertCount(1, $files);
        
        $filePerms = fileperms($files[0]) & 0777;
        $this->assertEquals(0600, $filePerms);
        
        // Clean up
        unlink($files[0]);
        rmdir($testDir);
    }
    
    /**
     * @test
     */
    public function testFileStorageAtomicWrite() {
        $validKey = KeyManager::generateKey();
        KeyManager::setEncryptionKey($validKey);
        
        $testDir = __DIR__ . '/test_cache';
        $storage = new FileStorage($testDir);
        
        $item = new Item('test', 'data', 60, $validKey);
        $storage->store($item);
        
        // Verify no temporary files remain
        $tempFiles = glob($testDir . '/*.tmp');
        $this->assertCount(0, $tempFiles);
        
        // Verify cache file exists
        $cacheFiles = glob($testDir . '/*.cache');
        $this->assertCount(1, $cacheFiles);
        
        // Clean up
        unlink($cacheFiles[0]);
        rmdir($testDir);
    }
    
    /**
     * @test
     */
    public function testEncryptionDecryptionRoundTrip() {
        $validKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $validKey;
        KeyManager::clearCache(); // Force reload from environment
        
        $complexData = [
            'string' => 'test string',
            'number' => 12345,
            'array' => [1, 2, 3],
            'object' => (object)['prop' => 'value']
        ];
        
        Cache::set('complex_data', $complexData, 60);
        $retrieved = Cache::get('complex_data');
        
        $this->assertEquals($complexData, $retrieved);
    }
    
    /**
     * @test
     */
    public function testKeyManagerEnvironmentVariable() {
        $validKey = KeyManager::generateKey();
        $_ENV['CACHE_ENCRYPTION_KEY'] = $validKey;
        
        KeyManager::clearCache();
        $retrievedKey = KeyManager::getEncryptionKey();
        
        $this->assertEquals($validKey, $retrievedKey);
        
        // Clean up
        unset($_ENV['CACHE_ENCRYPTION_KEY']);
    }
    
    /**
     * @test
     */
    public function testSecurityConfigEnvironmentVariables() {
        $_ENV['CACHE_ENCRYPTION_ENABLED'] = 'false';
        $_ENV['CACHE_ENCRYPTION_ALGORITHM'] = 'aes-128-cbc';
        $_ENV['CACHE_FILE_PERMISSIONS'] = '644';
        $_ENV['CACHE_DIR_PERMISSIONS'] = '755';
        
        $config = new SecurityConfig();
        
        $this->assertFalse($config->isEncryptionEnabled());
        $this->assertEquals('aes-128-cbc', $config->getEncryptionAlgorithm());
        $this->assertEquals(0644, $config->getFilePermissions());
        $this->assertEquals(0755, $config->getDirectoryPermissions());
        
        // Clean up
        unset($_ENV['CACHE_ENCRYPTION_ENABLED']);
        unset($_ENV['CACHE_ENCRYPTION_ALGORITHM']);
        unset($_ENV['CACHE_FILE_PERMISSIONS']);
        unset($_ENV['CACHE_DIR_PERMISSIONS']);
    }
    
    /**
     * @test
     */
    public function testItemClass() {
        $key = 'test_item';
        $data = 'test data';
        $ttl = 300;
        $secretKey = KeyManager::generateKey();
        
        $item = new Item($key, $data, $ttl, $secretKey);
        
        $this->assertEquals($key, $item->getKey());
        $this->assertEquals($data, $item->getData());
        $this->assertEquals($ttl, $item->getTTL());
        $this->assertEquals($secretKey, $item->getSecret());
        $this->assertGreaterThan(0, $item->getCreatedAt());
        $this->assertGreaterThan(time(), $item->getExpiryTime());
    }
    
    /**
     * @test
     */
    public function testItemSetters() {
        $item = new Item();
        
        $item->setKey('new_key');
        $this->assertEquals('new_key', $item->getKey());
        
        $item->setData('new_data');
        $this->assertEquals('new_data', $item->getData());
        
        $item->setTTL(500);
        $this->assertEquals(500, $item->getTTL());
        
        $createdAt = time() - 100;
        $item->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $item->getCreatedAt());
        
        $item->setPrefix('test_prefix_');
        $this->assertEquals('test_prefix_', $item->getPrefix());
    }
    
    /**
     * @test
     */
    public function testItemInvalidTTL() {
        $item = new Item('test', 'data', 100);
        
        // Negative TTL should not be set
        $item->setTTL(-50);
        $this->assertEquals(100, $item->getTTL()); // Should remain unchanged
        
        // Zero TTL should be allowed
        $item->setTTL(0);
        $this->assertEquals(0, $item->getTTL());
    }
    
    /**
     * @test
     */
    public function testItemInvalidCreatedAt() {
        $item = new Item('test', 'data', 100);
        $originalCreatedAt = $item->getCreatedAt();
        
        // Negative or zero timestamp should not be set
        $item->setCreatedAt(-100);
        $this->assertEquals($originalCreatedAt, $item->getCreatedAt());
        
        $item->setCreatedAt(0);
        $this->assertEquals($originalCreatedAt, $item->getCreatedAt());
        
        // Valid timestamp should be set
        $newTimestamp = time() + 100;
        $item->setCreatedAt($newTimestamp);
        $this->assertEquals($newTimestamp, $item->getCreatedAt());
    }
    
    /**
     * @test
     */
    public function testItemGenerateKey() {
        $key1 = Item::generateKey();
        $key2 = Item::generateKey();
        
        $this->assertEquals(64, strlen($key1));
        $this->assertEquals(64, strlen($key2));
        $this->assertTrue(ctype_xdigit($key1));
        $this->assertTrue(ctype_xdigit($key2));
        $this->assertNotEquals($key1, $key2); // Should be unique
    }
    
    /**
     * @test
     */
    public function testItemDataEncryptionState() {
        $item = new Item('test', 'data', 60);
        
        // Initially data should not be marked as encrypted
        $this->assertFalse($item->isDataEncrypted());
        
        // Mark as encrypted
        $item->setDataIsEncrypted(true);
        $this->assertTrue($item->isDataEncrypted());
        
        // Mark as not encrypted
        $item->setDataIsEncrypted(false);
        $this->assertFalse($item->isDataEncrypted());
    }
    
    /**
     * @test
     */
    public function testItemWithEmptySecret() {
        $item = new Item('test', 'data', 60, '');
        $this->assertEquals('', $item->getSecret());
        
        // Should work without encryption
        $config = new SecurityConfig();
        $config->setEncryptionEnabled(false);
        $item->setSecurityConfig($config);
        
        $encrypted = $item->getDataEncrypted();
        $decrypted = $item->getDataDecrypted();
        
        $this->assertEquals('data', $decrypted);
    }
    
    /**
     * @test
     */
    public function testSecurityConfigWithInvalidEnvironmentValues() {
        // Set invalid boolean value
        $_ENV['CACHE_ENCRYPTION_ENABLED'] = 'invalid_boolean';
        $_ENV['CACHE_FILE_PERMISSIONS'] = 'invalid_octal';
        $_ENV['CACHE_DIR_PERMISSIONS'] = 'invalid_octal';
        
        $config = new SecurityConfig();
        
        // Should handle invalid values gracefully
        $this->assertIsBool($config->isEncryptionEnabled());
        $this->assertIsInt($config->getFilePermissions());
        $this->assertIsInt($config->getDirectoryPermissions());
        
        // Clean up
        unset($_ENV['CACHE_ENCRYPTION_ENABLED']);
        unset($_ENV['CACHE_FILE_PERMISSIONS']);
        unset($_ENV['CACHE_DIR_PERMISSIONS']);
    }
    
    /**
     * @test
     */
    public function testFileStorageInvalidPath() {
        $this->expectException(\WebFiori\Cache\Exceptions\CacheStorageException::class);
        $this->expectExceptionMessage('Cache path cannot be empty');
        
        new FileStorage('');
    }
    
    /**
     * @test
     */
    public function testFileStorageNonWritablePath() {
        // Create a directory and make it non-writable
        $testDir = __DIR__ . '/non_writable_test';
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755);
        }
        chmod($testDir, 0444); // Read-only
        
        try {
            $this->expectException(\WebFiori\Cache\Exceptions\CacheStorageException::class);
            $this->expectExceptionMessage('Cache path is not writable');
            
            new FileStorage($testDir);
        } finally {
            // Clean up
            chmod($testDir, 0755);
            rmdir($testDir);
        }
    }
    
    /**
     * @test
     */
    public function testFileStorageFileAsPath() {
        // Create a file instead of directory
        $testFile = __DIR__ . '/test_file';
        file_put_contents($testFile, 'test');
        
        try {
            $this->expectException(\WebFiori\Cache\Exceptions\CacheStorageException::class);
            $this->expectExceptionMessage('Cache path exists but is not a directory');
            
            new FileStorage($testFile);
        } finally {
            // Clean up
            unlink($testFile);
        }
    }
    
    /**
     * @test
     */
    public function testFileStorageOperations() {
        $testDir = __DIR__ . '/test_file_storage';
        $storage = new FileStorage($testDir);
        
        $key = 'test_key';
        $data = 'test_data';
        $ttl = 300;
        
        // Test storage path
        $this->assertEquals($testDir, $storage->getPath());
        
        // Test item doesn't exist initially
        $this->assertFalse($storage->has($key, ''));
        $this->assertNull($storage->read($key, ''));
        $this->assertNull($storage->readItem($key, ''));
        
        // Store an item
        $item = new Item($key, $data, $ttl, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Test item exists and can be read
        $this->assertTrue($storage->has($key, ''));
        $this->assertEquals($data, $storage->read($key, ''));
        
        $retrievedItem = $storage->readItem($key, '');
        $this->assertNotNull($retrievedItem);
        $this->assertEquals($key, $retrievedItem->getKey());
        $this->assertEquals($ttl, $retrievedItem->getTTL());
        
        // Test delete
        $storage->delete($key);
        $this->assertFalse($storage->has($key, ''));
        
        // Clean up
        if (is_dir($testDir)) {
            $files = glob($testDir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($testDir);
        }
    }
    
    /**
     * @test
     */
    public function testFileStorageWithPrefix() {
        $testDir = __DIR__ . '/test_prefix_storage';
        $storage = new FileStorage($testDir);
        
        $key = 'test_key';
        $prefix1 = 'prefix1_';
        $prefix2 = 'prefix2_';
        
        // Store items with different prefixes
        $item1 = new Item($key, 'data1', 300, KeyManager::getEncryptionKey());
        $item1->setPrefix($prefix1);
        $storage->store($item1);
        
        $item2 = new Item($key, 'data2', 300, KeyManager::getEncryptionKey());
        $item2->setPrefix($prefix2);
        $storage->store($item2);
        
        // Test items exist with their respective prefixes
        $this->assertTrue($storage->has($key, $prefix1));
        $this->assertTrue($storage->has($key, $prefix2));
        $this->assertEquals('data1', $storage->read($key, $prefix1));
        $this->assertEquals('data2', $storage->read($key, $prefix2));
        
        // Test flush with prefix
        $storage->flush($prefix1);
        $this->assertFalse($storage->has($key, $prefix1));
        $this->assertTrue($storage->has($key, $prefix2));
        
        // Clean up
        $storage->flush($prefix2);
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
    }
    
    /**
     * @test
     */
    public function testFileStorageExpiredItems() {
        $testDir = __DIR__ . '/test_expired_storage';
        $storage = new FileStorage($testDir);
        
        $key = 'expired_key';
        $data = 'expired_data';
        
        // Create an item with very short TTL
        $item = new Item($key, $data, 1, KeyManager::getEncryptionKey());
        $storage->store($item);
        
        // Item should exist initially
        $this->assertTrue($storage->has($key, ''));
        $this->assertEquals($data, $storage->read($key, ''));
        
        // Wait for expiration
        sleep(2);
        
        // Item should be expired and automatically deleted
        $this->assertFalse($storage->has($key, ''));
        $this->assertNull($storage->read($key, ''));
        $this->assertNull($storage->readItem($key, ''));
        
        // Clean up
        if (is_dir($testDir)) {
            $files = glob($testDir . '/*.cache');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($testDir);
        }
    }
    
    /**
     * @test
     */
    public function testEncryptionWithDifferentAlgorithms() {
        $algorithms = ['aes-256-cbc', 'aes-128-cbc'];
        
        foreach ($algorithms as $algorithm) {
            $config = new SecurityConfig();
            $config->setEncryptionAlgorithm($algorithm);
            
            $item = new Item('test', 'sensitive data', 60, KeyManager::generateKey());
            $item->setSecurityConfig($config);
            
            $encrypted = $item->getDataEncrypted();
            $decrypted = $item->getDataDecrypted();
            
            $this->assertNotEquals('sensitive data', $encrypted);
            $this->assertEquals('sensitive data', $decrypted);
        }
    }
    
    /**
     * @test
     */
    public function testExceptionClasses() {
        // Test CacheException
        $cacheException = new \WebFiori\Cache\Exceptions\CacheException('Test message', 123);
        $this->assertEquals('Test message', $cacheException->getMessage());
        $this->assertEquals(123, $cacheException->getCode());
        
        // Test InvalidCacheKeyException
        $keyException = new \WebFiori\Cache\Exceptions\InvalidCacheKeyException('bad_key', 'Invalid format');
        $this->assertStringContainsString('bad_key', $keyException->getMessage());
        $this->assertStringContainsString('Invalid format', $keyException->getMessage());
        
        // Test CacheStorageException
        $storageException = new \WebFiori\Cache\Exceptions\CacheStorageException('Storage failed');
        $this->assertEquals('Storage failed', $storageException->getMessage());
        
        // Test CacheDriverException
        $driverException = new \WebFiori\Cache\Exceptions\CacheDriverException('TestDriver', 'testOperation');
        $this->assertStringContainsString('TestDriver', $driverException->getMessage());
        $this->assertStringContainsString('testOperation', $driverException->getMessage());
    }
    
    /**
     * @test
     */
    public function testKeyManagerClearCache() {
        $key1 = KeyManager::generateKey();
        KeyManager::setEncryptionKey($key1);
        $this->assertEquals($key1, KeyManager::getEncryptionKey());
        
        KeyManager::clearCache();
        
        // After clearing cache, it should reload from environment
        $envKey = $_ENV['CACHE_ENCRYPTION_KEY'];
        $this->assertEquals($envKey, KeyManager::getEncryptionKey());
    }
    
    /**
     * @test
     */
    public function testItemWithComplexSecurityConfig() {
        $config = new SecurityConfig();
        $config->setEncryptionEnabled(true);
        $config->setEncryptionAlgorithm('aes-256-cbc');
        $config->setFilePermissions(0640);
        $config->setDirectoryPermissions(0750);
        
        $item = new Item('complex_test', 'complex data', 60, KeyManager::generateKey());
        $item->setSecurityConfig($config);
        
        $retrievedConfig = $item->getSecurityConfig();
        $this->assertTrue($retrievedConfig->isEncryptionEnabled());
        $this->assertEquals('aes-256-cbc', $retrievedConfig->getEncryptionAlgorithm());
        $this->assertEquals(0640, $retrievedConfig->getFilePermissions());
        $this->assertEquals(0750, $retrievedConfig->getDirectoryPermissions());
    }
}

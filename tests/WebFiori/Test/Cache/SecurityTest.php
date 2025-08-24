<?php
namespace WebFiori\Test\Cache;

use PHPUnit\Framework\TestCase;
use WebFiori\Cache\Cache;
use WebFiori\Cache\Item;
use WebFiori\Cache\KeyManager;
use WebFiori\Cache\SecurityConfig;
use WebFiori\Cache\FileStorage;
use InvalidArgumentException;
use RuntimeException;

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
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid encryption key. Must be 64 hexadecimal characters.');
        KeyManager::setEncryptionKey('invalid_key');
    }
    
    /**
     * @test
     */
    public function testKeyManagerRejectsShortKey() {
        $this->expectException(InvalidArgumentException::class);
        KeyManager::setEncryptionKey('abc123'); // Too short
    }
    
    /**
     * @test
     */
    public function testKeyManagerRejectsNonHexKey() {
        $this->expectException(InvalidArgumentException::class);
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
            
            $this->expectException(RuntimeException::class);
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
        
        // Test decryption
        $decrypted = $item->getDataDecrypted();
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
            
            $this->expectException(RuntimeException::class);
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
}

<?php
namespace WebFiori\Cache\Exceptions;

/**
 * Exception thrown when there are issues with the underlying storage mechanism.
 * 
 * This exception is thrown when operations like reading from or writing to
 * the cache storage fail due to storage-related problems such as:
 * - File system permissions
 * - Disk space issues
 * - Network connectivity problems (for distributed caches)
 * - Database connection issues
 */
class CacheStorageException extends CacheException
{
    /**
     * Creates a new storage exception.
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $message = 'Cache storage operation failed', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

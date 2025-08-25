<?php
namespace WebFiori\Cache\Exceptions;

/**
 * Exception thrown when cache keys are malformed or invalid.
 * 
 * This exception is thrown when:
 * - Cache key is empty or null
 * - Cache key contains invalid characters
 * - Cache key exceeds maximum length limits
 * - Cache key format doesn't meet requirements
 */
class InvalidCacheKeyException extends CacheException
{
    /**
     * Creates a new invalid cache key exception.
     * 
     * @param string $key The invalid cache key that caused the exception
     * @param string $reason Additional reason for the invalid key
     * @param int $code The exception code
     * @param \Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $key = '', string $reason = '', int $code = 0, ?\Exception $previous = null)
    {
        $message = 'Invalid cache key';
        
        if (!empty($key)) {
            $message .= ": '{$key}'";
        }
        
        if (!empty($reason)) {
            $message .= " - {$reason}";
        }
        
        parent::__construct($message, $code, $previous);
    }
}

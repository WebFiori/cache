<?php
namespace WebFiori\Cache\Exceptions;

/**
 * Exception thrown when there are problems with custom cache drivers.
 * 
 * This exception is thrown when:
 * - Custom driver implementation is invalid
 * - Driver initialization fails
 * - Driver-specific operations fail
 * - Driver doesn't implement required interface properly
 */
class CacheDriverException extends CacheException
{
    /**
     * Creates a new cache driver exception.
     * 
     * @param string $driverName The name or class of the problematic driver
     * @param string $operation The operation that failed
     * @param int $code The exception code
     * @param \Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $driverName = '', string $operation = '', int $code = 0, ?\Exception $previous = null)
    {
        $message = 'Cache driver error';
        
        if (!empty($driverName)) {
            $message .= " in driver '{$driverName}'";
        }
        
        if (!empty($operation)) {
            $message .= " during operation '{$operation}'";
        }
        
        parent::__construct($message, $code, $previous);
    }
}

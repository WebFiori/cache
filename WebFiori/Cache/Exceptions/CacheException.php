<?php
namespace WebFiori\Cache\Exceptions;

use Exception;

/**
 * Base exception class for all cache-related errors.
 * 
 * This is the parent exception that all other cache exceptions extend from.
 * It provides a common interface for handling cache-related errors.
 */
class CacheException extends Exception
{
    /**
     * Creates a new instance of the exception.
     * 
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Exception|null $previous Previous exception for chaining
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

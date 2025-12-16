<?php

namespace Villaflor\Connection\Exception;

use Exception;

/**
 * Exception thrown when a connection to the remote server fails.
 *
 * This exception is thrown for network-level errors such as DNS resolution
 * failures, connection timeouts, or inability to establish a connection.
 */
class ConnectionException extends Exception
{
    /**
     * Create a new connection exception.
     *
     * @param  string  $message  The error message
     * @param  int  $code  The error code (typically cURL error number)
     * @param  Exception|null  $previous  Previous exception in the chain
     */
    public function __construct(string $message, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

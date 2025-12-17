<?php

namespace Villaflor\Connection\Exception;

/**
 * Exception thrown when a request times out.
 *
 * This exception is thrown when either the connection timeout or the
 * request timeout is exceeded.
 */
class TimeoutException extends ConnectionException
{
    /**
     * Create a new timeout exception.
     *
     * @param  string  $message  The error message
     * @param  int  $timeout  The timeout value that was exceeded
     */
    public function __construct(string $message = 'Request timed out', int $timeout = 0)
    {
        parent::__construct($message, $timeout);
    }
}

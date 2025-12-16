<?php

namespace Villaflor\Connection\Exception;

use Exception;

/**
 * Exception thrown when a response is malformed or invalid.
 *
 * This exception is thrown when the response from the server cannot be
 * parsed or is not in the expected format.
 */
class InvalidResponseException extends Exception
{
    /**
     * Create a new invalid response exception.
     *
     * @param  string  $message  The error message
     * @param  Exception|null  $previous  Previous exception in the chain
     */
    public function __construct(string $message = 'Invalid response received', ?Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}

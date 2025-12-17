<?php

namespace Villaflor\Connection\Exception;

use Exception;
use Psr\Http\Message\ResponseInterface;

/**
 * Exception thrown when an HTTP request fails or returns an error status code.
 *
 * This exception is thrown for HTTP 4xx and 5xx responses, as well as cURL errors.
 * It intelligently parses various JSON error response formats to provide meaningful
 * error messages.
 */
class ResponseException extends Exception
{
    /**
     * Create a ResponseException from a PSR-7 Response.
     *
     * Automatically parses JSON error responses in various formats:
     * - {"errors": [{"code": 123, "message": "Error"}]}
     * - {"message": "Error message"}
     * - {"error": "Error string"}
     * - {"error": {"message": "Error message"}}
     *
     * @param  ResponseInterface  $response  The HTTP response that caused the error
     * @param  Exception|null  $previous  Previous exception in the chain
     * @return self The created exception instance
     */
    public static function fromResponse(ResponseInterface $response, ?Exception $previous = null): self
    {
        $statusCode = $response->getStatusCode();
        $contentType = $response->getHeaderLine('Content-Type');
        $body = (string) $response->getBody();

        // Attempt to derive detailed error from standard JSON response.
        if (str_contains($contentType, 'application/json') && ! empty($body)) {
            $json = json_decode($body);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new ResponseException(
                    "HTTP {$statusCode}: JSON decode error",
                    $statusCode,
                    new JSONException(json_last_error_msg(), 0, $previous)
                );
            }

            if (isset($json->errors) && count($json->errors) >= 1) {
                return new ResponseException(
                    $json->errors[0]->message ?? "HTTP {$statusCode} error",
                    (int) ($json->errors[0]->code ?? $statusCode),
                    $previous
                );
            }

            if (isset($json->message)) {
                return new ResponseException($json->message, $statusCode, $previous);
            }

            if (isset($json->error)) {
                $errorMessage = is_string($json->error) ? $json->error : ($json->error->message ?? "HTTP {$statusCode} error");

                return new ResponseException($errorMessage, $statusCode, $previous);
            }
        }

        $reasonPhrase = $response->getReasonPhrase();
        $message = ! empty($reasonPhrase) ? "HTTP {$statusCode}: {$reasonPhrase}" : "HTTP {$statusCode} error";

        if (! empty($body) && strlen($body) < 200) {
            $message .= " - {$body}";
        }

        return new ResponseException($message, $statusCode, $previous);
    }
}

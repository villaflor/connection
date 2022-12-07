<?php

namespace Villaflor\Connection\Exception;

use Exception;
use GuzzleHttp\Exception\RequestException;

class ResponseException extends Exception
{
    public static function fromRequestException(RequestException $err): self
    {
        if (! $err->hasResponse()) {
            return new ResponseException($err->getMessage(), 0, $err);
        }

        $response = $err->getResponse();
        $contentType = $response->getHeaderLine('Content-Type');

        // Attempt to derive detailed error from standard JSON response.
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode($response->getBody());
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new ResponseException($err->getMessage(), 0, new JSONException(json_last_error_msg(), 0, $err));
            }

            if (isset($json->errors) && count($json->errors) >= 1) {
                return new ResponseException($json->errors[0]->message, (int) $json->errors[0]->code, $err);
            }
        }

        return new ResponseException($err->getMessage(), 0, $err);
    }
}

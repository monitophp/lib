<?php
/**
 * Exception\BadRequestException.
 *
 * @version 1.0.0
 */

namespace MonitoLib\Exception;

class BadRequestException extends Exception
{
    public function __construct(?string $message = null, ?array $errors = null, ?int $code = 400, ?\Exception $previous = null)
    {
        http_response_code($code);
        parent::__construct($message, $errors, $code, $previous);
    }
}

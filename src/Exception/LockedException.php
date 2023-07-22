<?php
/**
 * Exception\LockedException.
 *
 * @version 1.0.0
 */

namespace MonitoLib\Exception;

class LockedException extends Exception
{
    public function __construct(?string $message = null, ?array $errors = null, ?int $code = 423, ?\Exception $previous = null)
    {
        http_response_code($code);
        parent::__construct($message, $errors, $code, $previous);
    }
}

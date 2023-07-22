<?php
/**
 * Exception\Exception.
 *
 * @version 1.1.0
 */

namespace MonitoLib\Exception;

use MonitoLib\App;

class Exception extends \Exception
{
    protected $errors = [];

    public function __construct(?string $message = null, ?array $errors = [], ?int $code = 409, ?\Exception $previous = null)
    {
        $this->errors = $errors ?? [];
        http_response_code($code);
        parent::__construct($message, $code, $previous);
        $this->log();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function log(): void
    {
        if (App::getDebug() > 0) {
            $filepath = App::getLogPath() . 'exceptions_' . date('Ymd') . '.log';
            error_log(date('c') . "\n", 3, $filepath);
            error_log($this . "\n", 3, $filepath);

            if (!empty($this->errors)) {
                error_log("Additional errors:\n", 3, $filepath);
                error_log(print_r($this->errors, true) . "\n", 3, $filepath);
            }

            error_log("\n", 3, $filepath);
        }
    }
}

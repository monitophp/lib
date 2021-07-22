<?php
/**
 * Exception handler
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2021
 *
 * @package MonitoLib
 */
namespace MonitoLib\Exception;

use \MonitoLib\App;

class Exception extends \Exception
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-06-18
    * Initial release
    */

    protected $errors = [];

    public function __construct(string $message = null, ?array $errors = null, ?int $code = 409, ?\Exception $previous = null)
    {
        $this->errors = $errors;
        http_response_code($code);
        parent::__construct($message, $code, $previous);
        $this->log();
    }
    public function getErrors() : array
    {
        return $this->errors ?? [];
    }
    private function log() : void
    {
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
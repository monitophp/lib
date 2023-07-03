<?php
/**
 * Exception handler
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2018 - 2021
 *
 * @package MonitoLib
 */
namespace MonitoLib\Exception;

class InvalidModel extends Exception
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2021-06-18
    * new: inheriting from \MonitoLib\Exception\Exception
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    public function __construct(string $message = null, ?array $errors = null, ?int $code = 500, ?\Exception $previous = null)
    {
        http_response_code($code);
        parent::__construct($message, $errors, $code, $previous);
    }
}
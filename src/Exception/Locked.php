<?php
/**
 * Exception handler
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2018 - 2021
 *
 * @package MonitoLib
 */
namespace MonitoLib\Exception;

class Locked extends Exception
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2021-06-18
    * new: inheriting from \MonitoLib\Exception\Exception
    *
    * 1.0.0 - 2020-08-06
    * Initial release
    */

    public function __construct(string $message = null, ?array $errors = null, ?int $code = 423, ?\Exception $previous = null)
    {
        http_response_code($code);
        parent::__construct($message, $errors, $code, $previous);
    }
}
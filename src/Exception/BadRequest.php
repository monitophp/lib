<?php
/**
 * Exception handler
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2018
 *
 * @package MonitoLib
 */
namespace MonitoLib\Exception;

class BadRequest extends \Exception
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    private $errors = [];

    public function __construct ($message = null, $errors = null, $code = 400, \Exception $previous = null)
    {
        $this->errors = $errors;
        http_response_code($code);
        parent::__construct($message, $code, $previous);
    }

    public function __toString ()
    {
        return $this->errors;
    }
    public function getErrors ()
    {
        return $this->errors;
    }
}
<?php
/**
 * Class for general validation
 * Classe para validações diversas
 * @author Joelson Batista <joelsonb@msn.com>
 * @version 2009-07-24
 * @copyright Copyright &copy; 2009
 * @package classes
 */
namespace MonitoLib;

class Validator
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    /**
     * valida e-mail
     * @param string $email
     * @return boolean
     */
    static public function email ($email)
    {
        if (preg_match("/(\w[-._\w]*\w@\w[-._\w]*\w\.\w{2,3})/", $email)) {
            return true;
        }
        return false;
    }
    static public function date ($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
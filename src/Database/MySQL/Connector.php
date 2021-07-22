<?php
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *
 * @package MonitoLib
 */
namespace MonitoLib\Database\MySQL;

use \MonitoLib\Functions;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;

class Connector extends \MonitoLib\Database\Connection
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2020-07-21
    * new: encrypted password
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    protected function connect()
    {
        try {
            $password = Functions::decrypt($this->pass, $this->name . $this->env);
            $string   = "mysql:host={$this->host};dbname={$this->database};charset=UTF8";
            $this->connection = new \PDO($string, $this->user, $password);
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ];

            throw new DatabaseError('Erro ao conectar no banco de dados', $error);
        }
    }
}
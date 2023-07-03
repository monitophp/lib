<?php
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *
 * @package MonitoLib
 */
namespace MonitoLib\Database\Connector;

use \MonitoLib\Functions;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;

class MongoDB extends Connection
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
            // $password = Functions::decrypt($this->pass, $this->name . $this->env);
            $string = "mongodb+srv://{$this->user}:{$this->pass}@{$this->host}/{$this->database}?retryWrites=true&w=majority";
            $this->connection = new \MongoDB\Client($string);
        } catch (\PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ];

            throw new DatabaseError('Erro ao conectar no banco de dados!', $error);
        }
    }
}
<?php
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2021
 *
 * @package MonitoLib\MongoDB
 */
namespace MonitoLib\Database\MongoDB;

use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;

class Connector extends \MonitoLib\Database\Connection
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-07-06
    * Initial release
    */

    protected function connect()
    {
        try {
            $password = Functions::decrypt($this->pass, $this->name . $this->env);
            $string = "mongodb+srv://{$this->user}:{$password}@{$this->host}/{$this->database}?retryWrites=true&w=majority";
            $this->connection = new \MongoDB\Client($string);
        } catch (DatabaseError $e) {
            $error = [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ];

            throw new DatabaseError('Erro ao conectar no banco de dados', $error);
        }
    }
}
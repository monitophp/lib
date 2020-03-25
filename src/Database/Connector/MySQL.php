<?php 
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *  
 * @package MonitoLib
 */
namespace MonitoLib\Database\Connector;

use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;

class MySQL extends Connection
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	protected function connect()
	{
		try {
			$this->connection = new \PDO("mysql:host={$this->server};dbname={$this->database};charset=UTF8", $this->user, $this->password);
			$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
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
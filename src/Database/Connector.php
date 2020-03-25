<?php 
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *  
 * @package MonitoLib
 */
namespace MonitoLib\Database;

use \MonitoLib\App;
use \MonitoLib\Exception\InternalError;

class Connector
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */
	private static $instance;

	private $connectionName;
	private $connections = [];

	private function __construct()
	{
		$file = App::getConfigPath() . 'database.json';

		if (!is_readable($file)) {
			throw new InternalError("Arquivo $file não encontrado ou usuário sem permissão!");
		}

		$db = json_decode(file_get_contents($file));
		
		if (is_null($db)) {
			throw new InternalError("O arquivo $file é inválido!");
		}
		
		$this->connections = new \stdClass;

		if (!empty($db)) {
			foreach ($db as $dk => $dv) {
				if (!isset($dv->database)) {
					$dv->database = null;
				}

				$class = '\MonitoLib\Database\Connector\\' . $dv->dbms;

				$this->connections->$dk = new $class($dv);
				// $this->connections->$dk->name     = $dk;
				// $this->connections->$dk->instance = null;
			}
		}
	}
	/**
	 * getInstance
	 *
	 * @return returns instance of \jLib\Connector;
	 */
	public static function getInstance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new \MonitoLib\Database\Connector;
		}

		return self::$instance;
	}
	public function getConnection($connectionName = null)
	{
		if (empty($this->connections)) {
			throw new InternalError('Não existem conexões configuradas!');
		}

		if (is_null($this->connectionName) && is_null($connectionName)) {
			throw new InternalError('Não existe uma conexão padrão e nenhuma conexão foi informada!');
		}

		if (is_null($connectionName)) {
			$connectionName = $this->connectionName;
		}

		if (!isset($this->connections->$connectionName)) {
			throw new InternalError("A conexão $connectionName não existe!");
		}

		// if (is_null($this->connections->$connectionName->instance)) {
		// 	switch ($this->connections->$connectionName->dbms) {
		// 		case 'MySQL':
		// 			return \MonitoLib\Database\Connector\MySQL::connect($this->connections->$connectionName);
		// 		case 'Oracle':
		// 			return \MonitoLib\Database\Connector\Oracle::connect($this->connections->$connectionName);
		// 		default:
		// 			throw new InternalError('Driver de conexão inválido!');
		// 	}

		// 	$this->dbms     = $this->connections->$connectionName->dbms;
		// 	$this->server   = $this->connections->$connectionName->server;
		// 	$this->user     = $this->connections->$connectionName->user;
		// 	$this->database = $this->connections->$connectionName->database;

		// 	$this->connections->$connectionName->instance = $obj;
		// }

		return $this->connections->$connectionName;
	}
	/**
	 * getConnectionsList
	 *
	 * @return array Connections list
	 */
	public static function getConnectionsList ()
	{
		return self::$connections;
	}
	/**
	 * setConnectionName
	 * 
	 * @param string $connectionName Connection name
	 */
	public function setConnectionName ($connectionName)
	{
		if (!isset($this->connections->$connectionName)) {
			throw new InternalError("A conexão $connectionName não existe!");
		}

		// $this->dbms = $this->connections->$connectionName->dbms;

		$this->connectionName = $connectionName;
	}
}
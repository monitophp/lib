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
    const VERSION = '2.1.0';
    /**
    * 2.1.0 - 2021-03-16
    * new: addParam
	*
    * 2.0.0 - 2020-09-18
    * new: static properties and methods
    * new: connection source, methods payload and return types
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */
	private static $active = [];
	private static $configured = [];
	private static $default;
	private static $instance;
	private static $params = [];

	public static function addParam(string $connectionName, string $param)
	{
		self::$params[$connectionName] = $param;
	}
	/**
	 * getInstance
	 *
	 * @return returns instance of \jLib\Connector;
	 */
	public static function getInstance() : self
	{
		if (!isset(self::$instance)) {
			self::$instance = new \MonitoLib\Database\Connector();
		}

		return self::$instance;
	}
	public static function getConnection($connectionName = null)
	{
		$connectionName = $connectionName ?? self::$default;

		if (is_null($connectionName)) {
			throw new InternalError('Não existe uma conexão padrão e nenhuma conexão foi informada');
		}

		$p = explode('.', $connectionName);

		$connection = $p[0] . (isset(self::$params[$connectionName]) ? ':' . self::$params[$connectionName] : '');
		$enviroment = $p[1] ?? App::getEnv();
		$name       = $connection . '.' . $enviroment;

		// Retorna a conexão, se configurada
		if (isset(self::$active[$name])) {
			return self::$active[$name];
		}

		if (!isset(self::$configured[$connection])) {
			throw new InternalError("A conexão $connection não existe");
		}

		if (!isset(self::$configured[$connection][$enviroment])) {
			throw new InternalError("O ambiente $enviroment não está configurado na conexão $connection");
		}

		$params = self::$configured[$connection][$enviroment];
		$params['name'] = $connection;
		$params['env']  = $enviroment;

		$dbms = self::$configured[$connection][$enviroment]['type'];

		if ($dbms === 'Rest') {
			return $params;
		} else {
			$class = '\MonitoLib\Database\Connector\\' . $dbms;

			if (!class_exists($class)) {
				throw new InternalError("Tipo de conexão $dbms inválido");
			}

			return self::$active[$name] = new $class($params);
		}
	}
	/**
	 * getConnectionsList
	 *
	 * @return array Connections list
	 */
	public static function getConnectionsList() : array
	{
		return self::$configured;
	}
	/**
	 * setConnectionName
	 *
	 * @param string $connectionName Connection name
	 */
	public static function setConnectionName(string $connectionName) : void
	{
		self::$default = $connectionName;
	}
	public static function setConnections(array $connections) : void
	{
		self::$configured = $connections;
	}
}
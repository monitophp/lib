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

class Connection
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2020-09-18
    * new: password and server properties and get/set methods renamed to pass and host
    *
    * 1.0.0 - 2020-03-20
    * first versioned
    */

    // TODO: completar parâmetros port e charset
	protected $chartset;
	protected $connection;
	protected $database;
	protected $host;
	protected $pass;
	protected $port;
	protected $type;
	protected $user;

	public function __construct($d)
	{
		$this->name     = $d['name'];
		$this->env      = $d['env'];
		$this->database = $d['database'] ?? null;
		$this->type     = $d['type'];
		$this->pass     = $d['pass'];
		$this->host     = $d['host'];
		$this->user     = $d['user'];
		$this->port     = $d['port'] ?? null;
		$this->charset  = $d['charset'] ?? null;
	}
	public function getConnection()
	{
	    if (is_null($this->connection)) {
	        $this->connect();
	    }

	    return $this->connection;
	}
	/**
	* getDatabase
	*
	* @return $database
	*/
	public function getDatabase()
	{
		return $this->database;
	}
	/**
	* getEnv
	*
	* @return $env
	*/
	public function getEnv()
	{
		return $this->env;
	}
	/**
	* getType
	*
	* @return $type
	*/
	public function getType()
	{
		return $this->type;
	}
	/**
	* getName
	*
	* @return $name
	*/
	public function getName()
	{
		return $this->name;
	}
	/**
	* getPass
	*
	* @return $pass
	*/
	public function getPass()
	{
		return $this->pass;
	}
	/**
	* getHost
	*
	* @return $host
	*/
	public function getHost()
	{
		return $this->host;
	}
	/**
	* getUser
	*
	* @return $user
	*/
	public function getUser()
	{
		return $this->user;
	}
	/**
	 * setDatabase
	 *
	 * @param $database
	 */
	public function setDatabase($database)
	{
		$this->database = $database;
		return $this;
	}
	/**
	 * setType
	 *
	 * @param $type
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}
	/**
	 * setPass
	 *
	 * @param $pass
	 */
	public function setPass($pass)
	{
		$this->pass = $pass;
		return $this;
	}
	/**
	 * setHost
	 *
	 * @param $host
	 */
	public function setHost($host)
	{
		$this->host = $host;
		return $this;
	}
	/**
	 * setUser
	 *
	 * @param $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
		return $this;
	}
}
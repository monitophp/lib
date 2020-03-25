<?php 
/**
 * Database connector
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2013 - 2018
 *  
 * @package MonitoLib
 */
namespace MonitoLib\Database\Connector;

use \MonitoLib\App;
use \MonitoLib\Exception\InternalError;

class Connection
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2020-03-20
    * first versioned
    */
	protected $connection;
	protected $database;
	protected $dbms;
	protected $password;
	protected $server;
	protected $user;

	public function __construct($d)
	{
		$this->database = $d->database;
		$this->dbms     = $d->dbms;
		$this->password = $d->password;
		$this->server   = $d->server;
		$this->user     = $d->user;
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
	* getDbms
	*
	* @return $dbms
	*/
	public function getDbms()
	{
		return $this->dbms;
	}
	/**
	* getPassword
	*
	* @return $password
	*/
	public function getPassword()
	{
		return $this->password;
	}
	/**
	* getServer
	*
	* @return $server
	*/
	public function getServer()
	{
		return $this->server;
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
	 * setDbms
	 *
	 * @param $dbms
	 */
	public function setDbms($dbms)
	{
		$this->dbms = $dbms;
		return $this;
	}
	/**
	 * setPassword
	 *
	 * @param $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
		return $this;
	}
	/**
	 * setServer
	 *
	 * @param $server
	 */
	public function setServer($server)
	{
		$this->server = $server;
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
<?php
namespace MonitoLib\App\Dto;

class User
{
	private $userId;
	private $name;
	private $username;

	/**
	* getUserId
	*
	* @return $userId
	*/
	public function getUserId()
	{
		return $this->userId;
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
	* getUsername
	*
	* @return $username
	*/
	public function getUsername()
	{
		return $this->username;
	}
	/**
	 * setUserId
	 *
	 * @param $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
		return $this;
	}
	/**
	 * setName
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	/**
	 * setUsername
	 *
	 * @param $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
		return $this;
	}
}
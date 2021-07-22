<?php
namespace MonitoLib\Type;

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
	public function getUserId() : ?int
	{
		return $this->userId;
	}
	/**
	* getName
	*
	* @return $name
	*/
	public function getName() : ?string
	{
		return $this->name;
	}
	/**
	* getUsername
	*
	* @return $username
	*/
	public function getUsername() : ?string
	{
		return $this->username;
	}
	/**
	 * setUserId
	 *
	 * @param $userId
	 */
	public function setUserId(int $userId)
	{
		$this->userId = $userId;
		return $this;
	}
	/**
	 * setName
	 *
	 * @param $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
		return $this;
	}
	/**
	 * setUsername
	 *
	 * @param $username
	 */
	public function setUsername(string $username)
	{
		$this->username = $username;
		return $this;
	}
}
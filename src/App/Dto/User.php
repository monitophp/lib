<?php
/**
 * App\Dto\User.
 *
 * @version 1.0.1
 */

namespace MonitoLib\App\Dto;

class User
{
    private $userId;
    private $name;
    private $username;

    public function getUserId()
    {
        return $this->userId;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }
}

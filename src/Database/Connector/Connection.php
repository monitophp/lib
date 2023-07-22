<?php
/**
 * Database\Connector\Connection.
 *
 * @version 1.2.0
 */

namespace MonitoLib\Database\Connector;

class Connection
{
    protected $charset;
    protected $chartset;
    protected $connection;
    protected $database;
    protected $env;
    protected $conn;
    protected $host;
    protected $name;
    protected $pass;
    protected $port;
    protected $type;
    protected $user;

    public function __construct($d)
    {
        $this->database = $d['database'] ?? null;
        $this->type = $d['type'];
        $this->pass = $d['pass'];
        $this->host = $d['host'];
        $this->user = $d['user'];
        $this->port = $d['port'] ?? null;
        $this->charset = $d['charset'] ?? null;
    }

    public function getConnection()
    {
        if (is_null($this->connection)) {
            $this->connect();
        }

        return $this->connection;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getEnv()
    {
        return $this->env;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPass()
    {
        return $this->pass;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setDatabase($database)
    {
        $this->database = $database;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setPass($pass)
    {
        $this->pass = $pass;

        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}

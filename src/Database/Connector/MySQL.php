<?php
/**
 * Database\Connector\MySQL.
 *
 * @version 1.2.0
 */

namespace MonitoLib\Database\Connector;

use MonitoLib\Exception\DatabaseErrorException;
use PDO;
use PDOException;

class MySQL extends Connection
{
    protected function connect()
    {
        try {
            $password = ml_decrypt($this->pass, $this->name . $this->env);
            $string = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            $this->connection = new PDO($string, $this->user, $password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            throw new DatabaseErrorException('Error connecting to database', $error);
        }
    }
}

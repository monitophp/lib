<?php
/**
 * Database\Connector\MongoDB.
 *
 * @version 1.1.1
 */

namespace MonitoLib\Database\Connector;

use MonitoLib\Exception\DatabaseErrorException;
use PDOException;

class MongoDB extends Connection
{
    protected function connect()
    {
        try {
            $string = "mongodb+srv://{$this->user}:{$this->pass}@{$this->host}/{$this->database}?retryWrites=true&w=majority";
            $this->connection = new \MongoDB\Client($string);
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

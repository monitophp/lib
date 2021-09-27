<?php
namespace MonitoLib\Database\MySQL;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;
use \MonitoLib\Database\Query\Dml;

class Dao extends \MonitoLib\Database\Dao implements \MonitoLib\Database\DaoInterface
{
    const VERSION = '1.0.2';
    /**
    * 1.0.2 - 2020-12-21
    * fix: remove connection object
    *
    * 1.0.1 - 2019-05-08
    * fix: checks returned value from get function
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    protected $dbms = 1;
    protected $filter;
    protected $lastId;
    private $affectedRows = 0;

    public function affectedRows() : int
    {
        return $this->affectedRows;
    }
    public function beginTransaction() : void
    {
        $this->getConnection()->beginTransaction();
    }
    public function commit() : void
    {
        $this->getConnection()->commit();
    }
    public function execute($stt)
    {
        try {
            $stt->execute();
            $this->affectedRows = $stt->rowCount();
            return $stt;
        } catch (\PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            throw new DatabaseError('Erro ao executar comando no banco de dados', $error);
        }
    }
    public function fetchAll($stt)
    {
    }
    public function fetchArrayAssoc($stt)
    {
        return $stt->fetch(\PDO::FETCH_ASSOC);
    }
    public function fetchArrayNum($stt)
    {
        return $stt->fetch(\PDO::FETCH_NUM);
    }
    public function parse(string $sql)
    {
        return $this->getConnection()->prepare($sql);
    }
    public function rollback() : void
    {
        $this->getConnection()->rollback();
    }
    /**
    * getLastId
    */
    public function getLastId() : int
    {
        return $this->lastInsertId();
    }
}
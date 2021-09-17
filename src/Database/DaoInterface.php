<?php
namespace MonitoLib\Database;

interface DaoInterface
{
    /**
    * 1.1.0 - 2021-07-06
    * new: add replace(), rem getById
	*
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    public function affectedRows() : int;
	public function beginTransaction() : void;
    public function commit() : void;
	// public function count();
	// public function dataset();
	// public function delete(...$params);
    public function execute($stt);
    public function fetchAll($stt) : array;
    public function fetchArrayAssoc($stt) : array;
    public function fetchArrayNum($stt) : array;
	// public function get(...$params);
	public function getLastId() : int;
	// public function insert($dto);
	// public function list();
    public function parse(string $sql);
	// public function replace($dto);
    public function rollback() : void;
	// public function truncate();
	// public function update($dto);
}
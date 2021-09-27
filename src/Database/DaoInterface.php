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

    public function affectedRows(): int;
    public function beginTransaction(): void;
    public function commit(): void;
    public function execute($stt);
    public function fetchAll($stt);
    public function fetchArrayAssoc($stt);
    public function fetchArrayNum($stt);
    public function getLastId(): int;
    public function parse(string $sql);
    public function rollback(): void;
}

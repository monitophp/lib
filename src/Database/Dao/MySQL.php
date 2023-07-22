<?php
/**
 * Database\Dao\MySQL.
 *
 * @version 1.1.0
 */

namespace MonitoLib\Database\Dao;

use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\DatabaseErrorException;
use PDO;
use PDOException;

class MySQL extends Base
{
    protected $dbms = 1;

    public function beginTransaction()
    {
        $this->getConnection()->beginTransaction();
    }

    public function commit()
    {
        $this->getConnection()->commit();
    }

    public function execute($stt)
    {
        try {
            $stt->execute();

            return $stt;
        } catch (PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            throw new DatabaseErrorException('An error has occurred on database', $error);
        }
    }

    public function fetchArrayAssoc($stt)
    {
        return $stt->fetch(PDO::FETCH_ASSOC);
    }

    public function fetchArrayNum($stt)
    {
        return $stt->fetch(PDO::FETCH_NUM);
    }

    public function parse($sql)
    {
        return $this->getConnection()->prepare($sql);
    }

    public function rollback()
    {
        $this->getConnection()->rollback();
    }

    public function count()
    {
        $sql = $this->renderCountSql();
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);

        return $res[0];
    }

    public function dataset()
    {
        $data = [];
        $return = [];

        $sql = $this->renderCountSql(true);
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);

        $total = $res[0];
        $return['total'] = +$total;

        if ($total > 0) {
            $sql = $this->renderCountSql();
            $stt = $this->parse($sql);
            $this->execute($stt);
            $res = $this->fetchArrayNum($stt);

            $count = $res[0];
            $return['count'] = +$count;

            if ($count > 0) {
                $page = $this->getPage();
                $perPage = $this->getPerPage();
                $pages = $perPage > 0 ? ceil($count / $perPage) : 1;

                if ($page > $pages) {
                    throw new BadRequestException("Current page ({$page}) is bigger than total pages ({$pages})");
                }

                $data = $this->list();
                $return['data'] = $data;
                $return['page'] = +$page;
                $return['pages'] = +$pages;
            }
        }

        return $return;
    }

    /**
     * delete.
     *
     * @todo allow delete using dtoObject
     * @todo validate deleting without parameters
     * @todo validate deleting without all key parameters
     */
    public function delete()
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequestException('Unable to delete from a view');
        }

        $sql = $this->renderDeleteSql();
        $stt = $this->parse($sql);
        $this->execute($stt);
        $this->reset();
    }

    public function get()
    {
        $res = $this->list();

        return $res[0] ?? null;
    }

    public function getById(...$params)
    {
        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();
            $countKeys = count($keys);
            $countParams = count($params);

            if ($countKeys !== $countParams) {
                throw new BadRequestException('Invalid parameters');
            }

            if ($countParams > 1) {
                foreach ($params as $p) {
                    foreach ($keys as $k) {
                        $this->equal($k, $p);
                    }
                }
            } else {
                $this->equal($keys[0], $params[0]);
            }

            return $this->get();
        }
    }

    public function getLastId()
    {
        return $this->lastInsertId();
    }

    public function insert($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequestException('Unable to insert in a view');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequestException('Parameter is not an instance of ' . $this->dtoName);
        }

        $this->model->validate($dto);

        $dto = $this->setAutoValues($dto);

        $fld = '';
        $val = '';

        foreach ($this->model->getFieldsInsert() as $f) {
            $fld .= '`' . $f['name'] . '`,';
            $val .= ($f['transform'] ?? ':' . $f['name']) . ',';
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ({$fld}) VALUES ({$val})";
        $stt = $this->parse($sql);

        foreach ($this->model->getFieldsInsert() as $f) {
            $var = to_lower_camel_case($f['name']);
            $get = 'get' . ucfirst($var);
            ${$var} = $dto->{$get}();

            $stt->bindParam(':' . $f['name'], ${$var});
        }

        $this->execute($stt);
        $this->reset();
    }

    public function list(?string $sql = null): array
    {
        if (is_null($sql)) {
            $sql = $this->renderSelectSql();
        }

        $stt = $this->parse($sql);
        $this->execute($stt);

        $data = [];

        while ($res = $this->fetchArrayAssoc($stt)) {
            $data[] = $this->getValue($res);
        }

        $this->reset();

        return $data;
    }

    public function update($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequestException('Unable to update a view');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequestException('Parameter is not an instance of ' . $this->dtoName);
        }

        $this->model->validate($dto);

        $dto = $this->setAutoValues($dto);

        $key = '';
        $fld = '';

        foreach ($this->model->getFields() as $f) {
            $name = $f['name'];

            if ($f['primary']) {
                $key .= "`{$name}` = :{$name} AND ";
            } else {
                $fld .= "`{$name}` = " . ($f['transform'] ?? ":{$name}") . ',';
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);
        $sql = 'UPDATE ' . $this->model->getTableName() . " SET {$fld} WHERE {$key}";
        $stt = $this->parse($sql);

        foreach ($this->model->getFields() as $f) {
            $var = to_lower_camel_case($f['name']);
            $get = 'get' . ucfirst($var);
            ${$var} = $dto->{$get}();

            $stt->bindParam(':' . $f['name'], ${$var});
        }

        $this->execute($stt);
        $this->reset();

        $stt = null;
    }
}

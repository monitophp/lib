<?php
namespace MonitoLib\Database\MySQL;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;
use \MonitoLib\Database\Query\Dml;

class Dao extends \MonitoLib\Database\Dao
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

    protected $filter;
    protected $dbms = 1;
    private $dml;
    private $affectedRows = 0;

    public function beginTransaction()
    {
        $this->getConnection()->beginTransaction();
    }
    public function commit()
    {
        $this->getConnection()->commit();
    }
    // private function escape($value)
    // {
    //     return is_null($value) ? 'NULL' : "'$value'";
    // }
    public function execute($stt)
    {
        try {
            $stt->execute();
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
    public function rollback()
    {
        $this->getConnection()->rollback();
    }
    /**
    * delete
    * @todo allow delete using dtoObject
    * @todo validate deleting without parameters
    * @todo validate deleting without all key parameters
    */
    public function delete(...$params)
    {
        if ($this->model->getTableType() == 'view') {
            throw new BadRequest('Não é possível deletar registros de uma view');
        }

        $dml = $this->getDml();
        $sql = $dml->delete();
        \MonitoLib\Dev::ee($sql);

        $sql = 'DELETE FROM ';
        \MonitoLib\Dev::ee($sql);

        $sql = $this->renderDeleteSql();
        $stt = $this->parse($sql);
        $this->execute($stt);

        // Reset query
        $this->reset();

        $this->affectedRows = $stt->rowCount();

        // if ($stt->rowCount() === 0) {
            // throw new BadRequest('Não foi possível deletar!');
        // }
    }
    /**
    * get
    */
    public function get()
    {
        $res = $this->list();
        return isset($res[0]) ? $res[0] : null;
    }
    /**
    * getById
    */
    public function getById(...$params)
    {
        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();
            $countKeys   = count($keys);
            $countParams = count($params);

            if ($countKeys !== $countParams) {
                throw new BadRequest('Número inválido de parâmetros');
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
    /**
    * getLastId
    */
    public function getLastId()
    {
        return $this->lastInsertId();
    }
    /**
    * insert
    */
    public function insert($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível inserir registros em uma view');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName);
        }

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto);

        // Valida o objeto dto
        $validator = new \MonitoLib\Database\Validator();
        $validator->validate($dto, $this->model);

        // Verifica se existe constraint de chave única
        // $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        $fld = '';
        $val = '';

        $columns = $this->model->getInsertColumnsArray();
        // \MonitoLib\Dev::vde($columns);

        foreach ($columns as $column) {
            $id        = $column->getId();
            $name      = $column->getName();
            $transform = $column->getTransform();
            $get       = 'get' . ucfirst($id);
            $value     = $this->escape($dto->$get());

            $fld .= '`' . $name . '`,';
            // $val .= ($transform ?? ':' . $name) . ',';
            $val .= $value . ',';
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ($fld) VALUES ($val)";

        // \MonitoLib\Dev::ee($sql);
//
        $stt = $this->parse($sql);

        // foreach ($this->model->getFieldsInsert() as $f) {
        //     $var  = Functions::toLowerCamelCase($f['name']);
        //     $get  = 'get' . ucfirst($var);
        //     $$var = $dto->$get();

        //     $stt->bindParam(':' . $f['name'], $$var);
        // }

        $this->execute($stt);
        $this->reset();
    }
    private function getDml()
    {
        if (is_null($this->dml)) {
            $this->dml = new \MonitoLib\Database\Query\Dml($this->model, $this->dbms, $this->getFilter());
        }

        return $this->dml;
    }
    /**
    * update
    */
    public function update($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível atualizar os registros de uma view');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName);
        }

        // Valida o objeto dto
        // $this->model->validate($dto);

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto);

        // Valida o objeto dto
        $validator = new \MonitoLib\Database\Validator();
        $validator->validate($dto, $this->model);

        // Verifica se existe constraint de chave única
        // $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        $key = '';
        $fld = '';

        $columns = $this->model->getColumns();

        foreach ($columns as $column) {
            $id        = $column->getId();
            $name      = $column->getName();
            $primary   = $column->getPrimary();
            $transform = $column->getTransform();
            $get       = 'get' . ucfirst($id);
            $value     = $this->escape($dto->$get());

            if ($primary) {
                // $key .= "`$name` = :$name AND ";
                $key .= "`$name` = $value AND ";
            } else {
                // $fld .= "`$name` = " . ($transform ?? ":$name") . ',';
                $fld .= "`$name` = " . ($transform ?? "$value") . ',';
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);

        // \MonitoLib\Dev::pre($dto);

        $sql = 'UPDATE ' . $this->model->getTableName() . " SET $fld WHERE $key";
        // \MonitoLib\Dev::ee($sql);
        $stt = $this->parse($sql);

        // foreach ($this->model->getFields() as $f) {
        //     $var  = Functions::toLowerCamelCase($f['name']);
        //     $get  = 'get' . ucfirst($var);

        //     $stt->bindParam(':' . $f['name'], $$var);
        // }

        $this->execute($stt);

        $this->reset();

        $this->affectedRows = $stt->rowCount();

        \MonitoLib\Dev::e($this->affectedRows);

        // if ($stt->rowCount() === 0) {
            // throw new BadRequest("Não foi possível atualizar a tabela {$this->model->getTableName()}!");
        // }

        $stt = null;
    }
}
<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class MySQL extends Base implements \MonitoLib\Database\Dao
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2019-05-08
    * fix: checks returned value from get function
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

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
        } catch (\PDOException $e) {
            $error = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            throw new DatabaseError('Erro ao conectar no banco de dados!', $error);
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
    public function parse($sql)
    {
        return $this->getConnection()->prepare($sql);
    }
    public function rollback()
    {
        $this->getConnection()->rollback();
    }





    /**
    * count
    */
    public function count()
    {
        $sql = $this->renderCountSql();
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);
        return $res[0];
    }
    /**
    * dataset
    */
    public function dataset()
    {
        $data    = [];
        $return  = [];

        $sql = $this->renderCountSql(true);
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);

        $total = $res[0];
        $return['total'] = +$total;

        if ($total > 0) {
            $sql = $this->renderCountSql();
            $stt = $this->connection->parse($sql);
            $this->connection->execute($stt);
            $res = $this->connection->fetchArrayNum($stt);

            $count = $res[0];
            $return['count'] = +$count;

            if ($count > 0) {
                $page    = $this->getPage();
                $perPage = $this->getPerPage();
                $pages   = $perPage > 0 ? ceil($count / $perPage) : 1;

                if ($page > $pages) {
                    throw new BadRequest("Número da página atual ($page) maior que o número de páginas ($pages)!");
                }

                $data = $this->list();
                $return['data']  = $data;
                $return['page']  = +$page;
                $return['pages'] = +$pages;
            }
        }

        return $return;
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
            throw new BadRequest('Não é possível deletar registros de uma view!');
        }

        $sql = $this->renderDeleteSql();
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);

        // Reset query
        $this->reset();

        if ($stt->rowCount() === 0) {
            // throw new BadRequest('Não foi possível deletar!');
        }
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
                throw new BadRequest('Número inválido de parâmetros!');
            }

            if ($countParams > 1) {
                foreach ($params as $p) {
                    foreach ($keys as $k) {
                        $this->andEqual($k, $p);
                    }
                }
            } else {
                $this->andEqual($keys[0], $params[0]);
            }

            return $this->get();
        }
    }
    /**
    * getLastId
    */
    public function getLastId()
    {
        return $this->connection->lastInsertId();
    }
    /**
    * insert
    */
    public function insert($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível inserir registros em uma view!');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName . '!');
        }

        // Valida o objeto dto
        $this->model->validate($dto);

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto);

        // Verifica se existe constraint de chave única
        $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        $fld = '';
        $val = '';

        foreach ($this->model->getFieldsInsert() as $f) {
            $fld .= '`' . $f['name'] . '`,';
            $val .= ($f['transform'] ?? ':' . $f['name']) . ',';
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ($fld) VALUES ($val)";
        $stt = $this->connection->parse($sql);

        foreach ($this->model->getFieldsInsert() as $f) {
            $var  = Functions::toLowerCamelCase($f['name']);
            $get  = 'get' . ucfirst($var);
            $$var = $dto->$get();

            $stt->bindParam(':' . $f['name'], $$var);
        }

        $this->connection->execute($stt);
        $this->reset();
    }
    /**
    * list
    */
    public function list()
    {
        $sql = $this->renderSelectSql();
        $stt = $this->getConnection()->parse($sql);
        $this->getConnection()->execute($stt);

        $data = [];

        while ($res = $this->connection->fetchArrayAssoc($stt)) {
            $data[] = $this->getValue($res);
        }

        // Reset filter
        $this->reset();

        return $data;
    }
    /**
    * update
    */
    public function update($dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível inserir registros em uma view!');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName . '!');
        }

        // Valida o objeto dto
        $this->model->validate($dto);

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto);

        // Verifica se existe constraint de chave única
        $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        $key = '';
        $fld = '';

        foreach ($this->model->getFields() as $f) {
            $name = $f['name'];

            if ($f['primary']) {
                $key .= "`$name` = :$name AND ";
            } else {
                $fld .= "`$name` = " . ($f['transform'] ?? ":$name") . ',';
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);

        // \MonitoLib\Dev::pre($dto);

        $sql = 'UPDATE ' . $this->model->getTableName() . " SET $fld WHERE $key";
        // \MonitoLib\Dev::ee($sql);
        $stt = $this->connection->parse($sql);

        foreach ($this->model->getFields() as $f) {
            $var  = Functions::toLowerCamelCase($f['name']);
            $get  = 'get' . ucfirst($var);
            $$var = $dto->$get();

            $stt->bindParam(':' . $f['name'], $$var);
        }

        $this->connection->execute($stt);

        $this->reset();

        if ($stt->rowCount() === 0) {
            // throw new BadRequest("Não foi possível atualizar a tabela {$this->model->getTableName()}!");
        }

        $stt = null;
    }
}
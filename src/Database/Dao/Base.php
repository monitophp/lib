<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Base extends Query
{
    const VERSION = '1.1.2';
    /**
    * 1.1.2 - 2019-12-09
    * new: beginConnection(), commit() and rollback()
    * fix: getValue(): check if int values is null before set value
    *
    * 1.1.1 - 2019-08-11
    * fix: minor fixes
    *
    * 1.1.0 - 2019-05-02
    * new: removed parent::__constructor call
    *
    * 1.0.0 - 2019-04-17
    * initial release
    */

    protected $connection;
    protected $dtoName;
    protected $model;

    public function __construct()
    {
        $classParts = explode('\\', get_class($this));
        $namespace  = join(array_slice($classParts, 0, -2), '\\') . '\\';
        $className  = end($classParts);
        $dto        = $namespace . 'dto\\' . $className;
        $model      = $namespace . 'model\\' . $className;

        if (class_exists($dto)) {
            $this->dtoName = $dto;
        }

        if (class_exists($model)) {
            $this->model = new $model;
        }
    }
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }
    public function checkUnique($uniqueConstraints, $dto)
    {
        // TODO: erro quando a constraint tem data
        return true;
        if (!empty($uniqueConstraints)) {
            $errors = [];

            foreach ($uniqueConstraints as $uk => $uv) {
                foreach ($uv as $k => $v) {
                    $get = 'get' . ucfirst($v);
                    $this->andEqual($v, $dto->$get());

                    // Se o dto tiver as chaves primárias com valores, inclui na busca
                    $primaryKeys = $this->model->getPrimaryKeys();

                    foreach ($primaryKeys as $k) {
                        $get = 'get' . ucfirst($k);
                        $val = $dto->$get();

                        if (!is_null($val)) {
                            $this->andNotEqual($k, $val);
                        }
                    }
                }

                if ($this->count() > 0) {
                    $errors[] = "Não foi possível validar a constraint {$uk}!";
                }
            }

            if (!empty($errors)) {
                throw new BadRequest('Ocorreu um erro na persistência dos dados!', $errors);
            }
        }
    }
    public function commit()
    {
        $this->getConnection()->commit();
    }
    public function createDto($result)
    {
        if (!is_null($this->model) && array_map('strtolower', array_keys($result)) === array_map('strtolower', $this->model->listFieldsNames())) {
            $dtoName = $this->getDtoName();
            $dto     = new $dtoName;
        } else {
            $dto = \MonitoLib\Database\Dto::get($result);
        }

        return $dto;
    }
    public function getConnection()
    {
        // \MonitoLib\Dev::vde(\MonitoLib\Database\Connector::getInstance()->getConnection());
        return \MonitoLib\Database\Connector::getInstance()->getConnection()->getConnection();
    }
    public function getDtoName()
    {
        if (is_null($this->dtoName)) {
            throw new InternalError('Objeto DTO não informado!');
        }

        return $this->dtoName;
    }
    public function getModel()
    {
        if (is_null($this->model)) {
            throw new InternalError('Objeto Model nulo!');
        }

        return $this->model;
    }
    public function getValue($result)
    {
        $dto    = $this->createDto($result);
        $fields = $this->getModelFields();

        foreach ($result as $f => $v) {
            $f   = Functions::toLowerCamelCase($f);
            $set = 'set' . ucfirst($f);

            if (isset($fields[$f])) {
                $field = $fields[$f];

                if ($field['type'] === 'int' || $field['type'] === 'double') {
                    if (!is_null($v)) {
                        $v = +$v;
                    }
                }
            }

            $dto->$set($v);
        }

        return $dto;
    }
    public function max($field)
    {
        $sql = "SELECT COALESCE(MAX($field), 0) FROM {$this->model->getTableName()}";
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);
        return $res[0];
    }
    protected function parseDeleteParams($params)
    {
        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();
            $countKeys   = count($keys);
            $countParams = count($params);

            if ($countKeys !== $countParams) {
                throw new BadRequest('Parâmetros inválidos!');
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
        }
    }
    public function rollback()
    {
        $this->connection->rollback();
    }
    protected function setAutoValues($dto)
    {
        $fields = $this->getModelFields();

        if (empty($fields)) {
            throw new InternalError('Campos do modelo não encontrados!');
        }

        foreach ($fields as $fn => $f) {
            $source      = $f['source'];
            $sourceParts = explode('.', $source);
            $sourceType  = $sourceParts[0];
            $get         = 'get' . ucfirst($fn);

            if (is_null($dto->$get()) && in_array($sourceType, ['MAX','PARAM','SEQUENCE','TABLE'])) {
                $set      = 'set' . ucfirst($fn);
                $value    = $dto->$get();
                $primary  = $f['primary'];
                $auto     = $f['auto'];

                $sourceValue = isset($sourceParts[1]) ? $sourceParts[1] : null;

                switch ($sourceType) {
                    case 'MAX':
                        $value = $this->max($f['name']) + 1;
                        break;
                    case 'PARAM':
                    case 'TABLE':
                        $sourceParts = explode('/', $sourceValue);
                        $value = $this->paramValue($sourceParts[0], $sourceParts[1]);
                        break;
                    case 'SEQUENCE':
                        $value = $this->nextValue($sourceValue);
                        break;
                    default:
                        throw new InternalError('Origem de valor inválida!');
                }

                if ($primary) {
                    $this->lastId = $value;
                }               

                $dto->$set($value);
            }
        }

        return $dto;
    }
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE ' . $this->model->getTableName();
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
    }
}
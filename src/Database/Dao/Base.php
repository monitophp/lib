<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\App;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Base // extends Query
{
    const VERSION = '1.2.0';
    /**
    * 1.2.0 - 2020-09-18
    * new: parseHook()
    *
    * 1.1.2 - 2019-12-09
    * new: beginConnection(), commit() and rollback()
    * fix: getValue(): check if int values is null before set value
    *
    * 1.1.1 - 2019-08-11
    * fix: minor fixes
    *
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
        $namespace  = join('\\', array_slice($classParts, 0, -2)) . '\\';
        $className  = end($classParts);
        $dto        = $namespace . 'Dto\\' . $className;
        $model      = $namespace . 'Model\\' . $className;

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
                    $this->equal($v, $dto->$get());

                    // Se o dto tiver as chaves primárias com valores, inclui na busca
                    $primaryKeys = $this->model->getPrimaryKeys();

                    foreach ($primaryKeys as $k) {
                        $get = 'get' . ucfirst($k);
                        $val = $dto->$get();

                        if (!is_null($val)) {
                            $this->notEqual($k, $val);
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
        if ($result instanceof \stdClass) {
            $result = json_decode(json_encode($result), true);
        }

        if (!is_null($this->model) && array_map('strtolower', array_keys($result)) === array_map('strtolower', $this->model->listFieldsNames())) {
            $dtoName = $this->getDtoName();
            $dto     = new $dtoName;
        } else {
            $dto = \MonitoLib\Database\Dto::get($result, $this->convertName);
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
            throw new InternalError('Objeto Dto não informado!');
        }

        return $this->dtoName;
    }
    public function getModel()
    {
        // $db = debug_backtrace();

        // \MonitoLib\Dev::pr($db);

        if (is_null($this->model)) {
            throw new InternalError('Objeto Model nulo!');
        }

        return $this->model;
    }
    public function getValue(array $result)
    {
        $dto    = $this->createDto($result);
        $fields = $this->getModelFields();

        foreach ($result as $f => $v) {
            $f   = $this->convertName ? Functions::toLowerCamelCase($f) : mb_strtolower($f);
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
        $stt = $this->parse($sql);
        $this->execute($stt);
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
                        $this->equal($k, $p);
                    }
                }
            } else {
                $this->equal($keys[0], $params[0]);
            }
        }
    }
    private function parseHook($hook, $value, $updating)
    {
        if (($hook === 'INSERT' && $updating) || ($hook === 'UPDATE' && !$updating)) {
            return null;
        }

        switch (mb_strtolower($value)) {
            case 'now':
                return App::now();
                break;
            case 'userid':
                return App::getUserId();
                break;
            default:
                return $value;
        }
    }
    public function rollback()
    {
        $this->connection->rollback();
    }
    protected function setAutoValues($dto, $updating = false)
    {
        $fields = $this->getModelFields();

        if (empty($fields)) {
            throw new InternalError('Campos do modelo não encontrados!');
        }

        // \MonitoLib\Dev::pre($fields);

        foreach ($fields as $fn => $f) {
            $source      = $f['source'];
            $sourceParts = explode('.', $source);
            $sourceType  = $sourceParts[0];
            $get         = 'get' . ucfirst($fn);
            $set         = 'set' . ucfirst($fn);
            $value       = $dto->$get();

            if (is_null($value)) {
                if (in_array($sourceType, ['MAX','PARAM','SEQUENCE','TABLE', 'INSERT', 'UPDATE'])) {
                    $value   = $dto->$get();
                    $primary = $f['primary'];
                    $auto    = $f['auto'];

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
                        case 'INSERT':
                        case 'UPDATE':
                            $value = $this->parseHook($sourceType, $sourceValue, $updating);
                            break;
                        default:
                            throw new InternalError('Origem de valor inválida!');
                    }

                    if ($primary) {
                        $this->lastId = $value;
                    }
                } elseif (!is_null($f['default'])) {
                    $value = $f['default'];
                }

                $dto->$set($value);
            }
        }

        return $dto;
    }
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE ' . $this->model->getTableName();
        $stt = $this->parse($sql);
        $this->execute($stt);
    }
}
<?php
/**
 * Database\Dao\Base.
 *
 * @version 1.3.0
 */

namespace MonitoLib\Database\Dao;

use MonitoLib\App;
use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\InternalErrorException;
use stdClass;

class Base
{
    protected $connection;
    protected $dtoName;
    protected $model;
    protected $lastId;
    protected $convertName;
    protected $dbms;
    protected $tableName;

    public function __construct()
    {
        $classParts = explode('\\', get_class($this));
        $namespace = join('\\', array_slice($classParts, 0, -2)) . '\\';
        $className = end($classParts);

        $dtoSuffix = '';
        $modelSuffix = '';

        if (substr($className, -3) === 'Dao') {
            $className = substr($className, 0, -3);
            $dtoSuffix = 'Dto';
            $modelSuffix = 'Model';
        }

        $dto = "{$namespace}Dto\\{$className}{$dtoSuffix}";
        $model = "{$namespace}Model\\{$className}{$modelSuffix}";

        if (class_exists($dto)) {
            $this->dtoName = $dto;
        }

        if (class_exists($model)) {
            $this->model = new $model();
        }
    }

    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    public function commit()
    {
        $this->getConnection($this->connection)->commit();
    }

    public function createDto($result)
    {
        if ($result instanceof stdClass) {
            $result = json_decode(json_encode($result), true);
        }

        if (!is_null($this->model) && array_map('strtolower', array_keys($result)) === array_map('strtolower', $this->model->listFieldsNames())) {
            $dtoName = $this->getDtoName();
            $dto = new $dtoName();
        } else {
            $dto = \MonitoLib\Database\Dto::get($result, $this->convertName);
        }

        return $dto;
    }

    public function getConnection()
    {
        return \MonitoLib\Database\Connector::getInstance()->getConnection($this->connection)->getConnection();
    }

    public function getDtoName()
    {
        if (is_null($this->dtoName)) {
            throw new InternalErrorException('Invalid DTO');
        }

        return $this->dtoName;
    }

    public function getModel()
    {
        if (is_null($this->model)) {
            throw new InternalErrorException('Invalid model');
        }

        return $this->model;
    }

    public function getValue(array $result)
    {
        $dto = $this->createDto($result);
        $fields = $this->getModelFields();

        foreach ($result as $f => $v) {
            $f = $this->convertName ? to_lower_camel_case($f) : mb_strtolower($f);
            $set = 'set' . ucfirst($f);

            if (isset($fields[$f])) {
                $field = $fields[$f];

                if ($field['type'] === 'int' || $field['type'] === 'double') {
                    if (!is_null($v)) {
                        $v = +$v;
                    }
                }
            }

            $dto->{$set}($v);
        }

        return $dto;
    }

    public function max($field)
    {
        $sql = "SELECT COALESCE(MAX({$field}), 0) FROM {$this->model->getTableName()}";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);

        return $res[0];
    }

    public function rollback()
    {
        $this->connection->rollback();
    }

    public function truncate()
    {
        $sql = 'TRUNCATE TABLE ' . $this->model->getTableName();
        $stt = $this->parse($sql);
        $this->execute($stt);
    }

    protected function parseDeleteParams($params)
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
        }
    }

    protected function setAutoValues($dto, $updating = false)
    {
        $fields = $this->getModelFields();

        if (empty($fields)) {
            throw new BadRequestException('Empty fields list');
        }

        foreach ($fields as $fn => $f) {
            $source = $f['source'] ?? '';
            $sourceParts = explode('.', $source);
            $sourceType = $sourceParts[0];
            $get = 'get' . ucfirst($fn);
            $set = 'set' . ucfirst($fn);
            $value = $dto->{$get}();

            if (is_null($value)) {
                if (in_array($sourceType, ['MAX', 'PARAM', 'SEQUENCE', 'TABLE', 'INSERT', 'UPDATE'])) {
                    $value = $dto->{$get}();
                    $primary = $f['primary'];
                    $auto = $f['auto'];

                    $sourceValue = $sourceParts[1] ?? null;

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
                            throw new InternalErrorException('Invalid source');
                    }

                    if ($primary) {
                        $this->lastId = $value;
                    }
                } elseif (!is_null($f['default'])) {
                    $value = $f['default'];
                }

                $dto->{$set}($value);
            }
        }

        return $dto;
    }

    private function parseHook($hook, $value, $updating)
    {
        if (('INSERT' === $hook && $updating) || ('UPDATE' === $hook && !$updating)) {
            return null;
        }

        switch (mb_strtolower($value)) {
            case 'now':
                return now();

            case 'userid':
                return App::getUserId();

            default:
                return $value;
        }
    }
}

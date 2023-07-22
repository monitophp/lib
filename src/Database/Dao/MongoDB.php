<?php
/**
 * Database\Dao\MongoDB.
 *
 * @version 1.1.0
 */

namespace MonitoLib\Database\Dao;

use DateTime;
use DateTimeZone;
use MonitoLib\Database\Connector;
use MonitoLib\Exception\BadRequestException;

class MongoDB extends \MonitoLib\Database\Query\MongoDB
{
    protected $dbms = 4;
    protected $lastId;

    public function count(): int
    {
        $filter = $this->renderFilter();
        $dtoName = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        return $collection->count($filter);
    }

    public function dataset()
    {
        $filter = $this->renderFilter();
        $options = $this->renderOptions();

        $dtoName = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        $count = $collection->count($filter);
        $total = $count;
        $data = [];

        if ($count > 0) {
            $cursor = $collection->find($filter, $options);

            foreach ($cursor as $document) {
                $data[] = $this->parseResult($document);
            }
        }

        $perPage = $this->getPerPage();

        if ($perPage <= 0) {
            $perPage = $count;
        }

        return new \MonitoLib\Database\Dataset\Dataset(
            $data,
            new \MonitoLib\Database\Dataset\Pagination(
                $total,
                $count,
                $this->getPage(),
                count($data),
                $perPage
            )
        );
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

        $dtoName = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        $filter = $this->renderFilter();
        $collection->delete($filter);

        $this->reset();
    }

    public function get(): ?object
    {
        $filter = $this->renderFilter();
        $options = $this->renderOptions();

        $dtoName = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        $result = $collection->findOne($filter, $options);

        if (is_null($result)) {
            return null;
        }

        $this->reset();

        return $this->parseResult($result);
    }

    public function getLastId()
    {
        return $this->lastId;
    }

    public function insert(object $dto)
    {
        $dtoName = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        $insert = $this->parseInsert($dto);
        $result = $collection->insertOne($insert);

        $this->lastId = $result->getInsertedId()->__toString();
        $this->reset();
    }

    public function list()
    {
        $filter = $this->renderFilter();
        $options = $this->renderOptions();

        $dtoName = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model = new $modelName();
        $table = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();
        $collection = $handler->{$database}->{$table};

        $count = $collection->count($filter);
        $total = $count;
        $data = [];

        if ($count > 0) {
            $cursor = $collection->find($filter, $options);

            foreach ($cursor as $document) {
                $data[] = $this->parseResult($document);
            }
        }

        $perPage = $this->getPerPage();

        if ($perPage <= 0) {
            $perPage = $count;
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'count' => $count,
                'page' => $this->getPage(),
                'perPage' => $perPage,
            ],
        ];
    }

    public function parseInsert(object $dto): array
    {
        $dtoName = get_class($dto);
        $modelName = str_replace('Dto', 'Model', $dtoName);
        $model = new $modelName();

        $insert = [];

        foreach ($model->getFieldsInsert() as $__fn => $__f) {
            $__name = $__f['name'] ?? $__fn;
            $__type = $__f['type'] ?? 'string';
            $__var = to_lower_camel_case($__name);
            $__get = 'get' . ucfirst($__var);
            ${$__var} = $dto->{$__get}();

            if (is_null(${$__var})) {
                continue;
            }

            $__value = '';

            if ('_id' === $__fn || 'id' === $__fn) {
                continue;
            }

            if ('_id' === $__name || 'id' === $__name) {
                continue;
            }

            if (is_array($__type)) {
                $__type = $__type[0] ?? null;
            }

            switch ($__type) {
                case 'date':
                case 'datetime':
                    $__value = new \MongoDB\BSON\UTCDateTime(new DateTime(${$__var}, new DateTimeZone(date_default_timezone_get())));

                    break;

                case 'int':
                    $__value = (int) ${$__var};

                    break;

                default:
                    if (class_exists($__type)) {
                        if (is_array(${$__var})) {
                            $__value = [];

                            foreach (${$__var} as $__v) {
                                $__value[] = $this->parseInsert($__v);
                            }
                        } else {
                            $__value = $this->parseInsert(${$__var});
                        }
                    } else {
                        $__value = ${$__var};
                    }
            }

            $insert[$__name] = $__value;
        }

        return $insert;
    }

    public function parseResult(object $result)
    {
        $result = $result->jsonSerialize();
        $daoName = get_class($this);
        $dtoName = str_replace('\\Dao\\', '\\Dto\\', $daoName);

        return $this->parseDto($dtoName, $result);
    }

    public function update(object $dto)
    {
        $dtoName = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        $model = new $modelName();
        $table = $model->getTablename();

        $filter = [
            '_id' => new \MongoDB\BSON\ObjectID($dto->getId()),
        ];

        $update = $this->parseInsert($dto);
        $connection = Connector::getInstance()->getConnection($this->connection);
        $database = $connection->getDatabase();
        $handler = $connection->getConnection();

        $collection = $handler->{$database}->{$table};
        $result = $collection->updateOne($filter, ['$set' => $update]);
    }

    private function parseDto(string $dtoName, object $document)
    {
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        $_dto = new $dtoName();
        $_model = new $modelName();

        foreach ($document as $_key => $_value) {
            if ($_key === '_id') {
                $_key = 'id';
            }

            $_field = $_model->getField($_key);

            if (empty($_field)) {
                continue;
            }

            $__type = $_field->getType() ?? 'string';

            switch ($__type) {
                case 'array':
                    $_value = json_decode(json_encode($_value), true);
                    break;

                case 'date':
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
                    $_value = $date->format('Y-m-d');
                    break;

                case 'datetime':
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new DateTimeZone(date_default_timezone_get()));
                    $_value = $date->format('Y-m-d H:i:s');
                    break;

                case 'oid':
                    $_value = $_value->__toString();
                    break;

                default:
                    if (is_array($__type)) {
                        $__type = $__type[0];

                        $_v1 = [];

                        foreach ($_value as $_v) {
                            $_v1[] = $this->parseDto($__type, $_v->jsonSerialize());
                        }

                        $_value = $_v1;
                    } else {
                        if (class_exists($__type)) {
                            if (!is_null($_value)) {
                                $_value = $this->parseDto($__type, $_value);
                            }
                        }
                    }
            }

            $_method = to_lower_camel_case($_key);
            $_set = 'set' . ucfirst($_method);

            if (method_exists($_dto, $_set)) {
                if (!is_null($_value)) {
                    $_dto->{$_set}($_value);
                }
            }
        }

        return $_dto;
    }
}

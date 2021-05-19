<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Database\Connector;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;

class MongoDB extends \MonitoLib\Database\Query\MongoDB
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-03-04
    * initial release
    */

    protected $dbms = 4;
    private $lastId;

    /**
    * count
    */
    public function count() : int
    {
        $filter    = $this->renderFilter();
        $dtoName   = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();
        $collection = $handler->$database->$table;

        return $collection->count($filter);
    }
    /**
    * dataset
    */
    public function dataset()
    {
        $filter  = $this->renderFilter();
        $options = $this->renderOptions();

        $dtoName   = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();
        $collection = $handler->$database->$table;

        $count = $collection->count($filter);
        $total = $count;
        $data  = [];

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

        // Creates the dataset
        $dataset = (new \MonitoLib\Database\Dataset\Dataset(
            $data,
            (new \MonitoLib\Database\Dataset\Pagination(
                $total,
                $count,
                $this->getPage(),
                count($data),
                $perPage
            ))
        ));

        // $dataset = [
        //     'data' => $data,
        //     'pagination' => [
        //         'total'   => $total,
        //         'count'   => $count,
        //         'page'    => $this->getPage(),
        //         'perPage' => $perPage,
        //     ]
        // ];

        return $dataset;
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


        $dtoName   = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();
        $collection = $handler->$database->$table;

        $filter = $this->renderFilter();
        $collection->delete($filter);

        // Reset query
        $this->reset();
    }
    // public function renderFindOne
    /**
    * get
    */
    public function get() : ?object
    {
        $filter  = $this->renderFilter();
        $options = $this->renderOptions();
        // \MonitoLib\Dev::pr($filter);

        $dtoName   = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();
        $collection = $handler->$database->$table;

        $result = $collection->findOne($filter, $options);

        if (is_null($result)) {
            return null;
        }

        $this->reset();

        return $this->parseResult($result);
    }
    /**
    * getLastId
    */
    public function getLastId()
    {
        return $this->lastId;
    }
    /**
    * insert
    */
    public function insert(object $dto)
    {
        $dtoName   = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection)->getConnection();
        $collection = $connection->fermento->$table;
        $insert     = $this->parseInsert($dto);
        $result     = $collection->insertOne($insert);

        $this->lastId = $result->getInsertedId()->__toString();
        $this->reset();
    }
    /**
    * list
    */
    public function list()
    {
        $filter  = $this->renderFilter();
        $options = $this->renderOptions();
        // \MonitoLib\Dev::pr($filter);
        // \MonitoLib\Dev::pre($options);

        $dtoName   = $this->dtoName;
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();
        $collection = $handler->$database->$table;

        $count = $collection->count($filter);
        $total = $count;
        $data  = [];

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

        // $dataset = new \MonitoLib\Database\Dataset(
        //     $total,
        //     $count,
        //     $this->getPage(),
        //     $perPage,
        //     $data
        // );

        $dataset = [
            'data' => $data,
            'pagination' => [
                'total'   => $total,
                'count'   => $count,
                'page'    => $this->getPage(),
                'perPage' => $perPage,
            ]
        ];

        return $dataset;
    }
    private function parseDto(string $dtoName, object $document)
    {
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        // \MonitoLib\Dev::pr($document);

        $_dto   = new $dtoName();
        $_model = new $modelName();

        // Percorre os campos do documento
        foreach ($document as $_key => $_value) {
            if ($_key === '_id') {
                $_key = 'id';
            }

            $_field = $_model->getField($_key);

            if (empty($_field)) {
                continue;
            }

            $__type = $_field->getType() ?? 'string';

            // if ($_key === 'invoices') {
            //     \MonitoLib\Dev::e($__type);
            //     \MonitoLib\Dev::vde($_value);
            // }

            switch ($__type) {
                case 'array':
                    $_value = json_decode(json_encode($_value), true);
                    break;
                case 'date':
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
                    $_value = $date->format('Y-m-d');
                    break;
                case 'datetime':
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
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
                            // \MonitoLib\Dev::pr($_v);
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
                // echo "key: $_key\n";
            }


            $_method = Functions::toLowerCamelCase($_key);
            $_set = 'set' . ucfirst($_method);

            if (method_exists($_dto, $_set)) {
                // echo get_class($_dto) . ', ' . $_set . " E\n";
                if (!is_null($_value)) {
                    $_dto->$_set($_value);
                }
            } else {
                // echo get_class($_dto) . ', ' . $_set . " N\n";
            }
        }

        return $_dto;
    }
    public function parseInsert(object $dto) : array
    {
        // \MonitoLib\Dev::pre($dto);
        $dtoName   = get_class($dto);
        $modelName = str_replace('Dto', 'Model', $dtoName);
        $model     = new $modelName();

        $insert = [];

        foreach ($model->getFieldsInsert() as $__fn => $__f) {
            // echo $__fn . "\n\n";

            $__name = $__f['name'] ?? $__fn;
            $__type = $__f['type'] ?? 'string';
            $__var  = Functions::toLowerCamelCase($__name);
            $__get  = 'get' . ucfirst($__var);
            $$__var = $dto->$__get();

            if (is_null($$__var)) {
                continue;
            }

            $__value = '';

            // \MonitoLib\Dev::e($__type);

            if ($__fn === '_id' || $__fn === 'id') {
                continue;
            }

            if ($__name === '_id' || $__name === 'id') {
                continue;
            }

            if (is_array($__type)) {
                $__type = $__type[0] ?? null;
            }

            switch ($__type) {
                case 'date':
                case 'datetime':
                    $__value = new \MongoDB\BSON\UTCDateTime(new \DateTime($$__var, new \DateTimeZone(date_default_timezone_get())));
                    break;
                case 'int':
                    $__value = (int)$$__var;
                    break;
                default:
                    if (class_exists($__type)) {
                        if (is_array($$__var)) {
                            $__value = [];
                            foreach ($$__var as $__v) {
                                $__value[] = $this->parseInsert($__v);
                            }
                        } else {
                            $__value = $this->parseInsert($$__var);
                        }
                    } else {
                        $__value = $$__var;
                    }
            }

            $insert[$__name] = $__value;


            // $__fld .= '`' . $name . '`,';
            // $val .= ($__f['transform'] ?? ':' . $name) . ',';
        }

        // \MonitoLib\Dev::pre($insert);

        return $insert;
    }
    public function parseResult(object $result)
    {
        $result  = $result->jsonSerialize();
        $daoName = get_class($this);
        $dtoName = str_replace('\\Dao\\', '\\Dto\\', $daoName);
        return $this->parseDto($dtoName, $result);
    }
    /**
    * update
    */
    public function update(object $dto)
    {
        $dtoName   = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        $model     = new $modelName();
        $table     = $model->getTablename();

        // \MonitoLib\Dev::pre($dto);

        $filter = [
            '_id' => new \MongoDB\BSON\ObjectID($dto->getId())
        ];

        // if (method_exists($this, 'beforeUpdate')) {
        //     $dto = $this->beforeUpdate($dto);
        // }


        $update = $this->parseInsert($dto);

        // \MonitoLib\Dev::pre($update);

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();

        $collection = $handler->$database->$table;
        $result = $collection->updateOne($filter, ['$set' => $update]);

    }
}
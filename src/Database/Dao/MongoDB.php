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
    public function count()
    {
        $sql = $this->renderCountSql();
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);
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
        $stt = $this->parse($sql);
        $this->execute($stt);

        // Reset query
        $this->reset();

        if ($stt->rowCount() === 0) {
            // throw new BadRequest('Não foi possível deletar!');
        }
    }
    // public function renderFindOne
    /**
    * get
    */
    public function get() : ?object
    {
        $filter  = $this->renderFilter();
        $options = $this->renderOptions();

        \MonitoLib\Dev::pr($filter);

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
    public function parseResult(object $result)
    {
        $result = $result->jsonSerialize();

        $daoName = get_class($this);
        $dtoName = str_replace('\\Dao\\', '\\Dto\\', $daoName);
        $dto = $this->parseDto($dtoName, $result);

        return $dto;
    }
    private function parseDto(string $dtoName, object $document)
    {
        // \MonitoLib\Dev::pre(json_encode($document));

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
    // /**
    // * getById
    // */
    // public function getById(...$params)
    // {
    //     if (!empty($params)) {
    //         $keys = $this->model->getPrimaryKeys();
    //         $countKeys   = count($keys);
    //         $countParams = count($params);

    //         if ($countKeys !== $countParams) {
    //             throw new BadRequest('Número inválido de parâmetros!');
    //         }

    //         if ($countParams > 1) {
    //             foreach ($params as $p) {
    //                 foreach ($keys as $k) {
    //                     $this->equal($k, $p);
    //                 }
    //             }
    //         } else {
    //             $this->equal($keys[0], $params[0]);
    //         }

    //         return $this->get();
    //     }
    // }
    /**
    * getLastId
    */
    public function getLastId()
    {
        return $this->lastId;
    }
    public function toInsert(object $dto) : array
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
                                $__value[] = $this->toInsert($__v);
                            }
                        } else {
                            $__value = $this->toInsert($$__var);
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
    /**
    * insert
    */
    public function insert($dto)
    {
        // \MonitoLib\Dev::pr($dto);

        $insert = $this->toInsert($dto);

        // \MonitoLib\Dev::pre($insert);

        $dtoName   = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        $model     = new $modelName();
        $table     = $model->getTablename();

        $connection = Connector::getInstance()->getConnection($this->connection)->getConnection();
        $collection = $connection->fermento->$table;
        $result     = $collection->insertOne($insert);

        $this->lastId = $result->getInsertedId()->__toString();

        $this->reset();
    }
    /**
    * list
    */
    public function list()
    {
        $data = [];

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

        if ($count > 0) {
            $cursor = $collection->find($filter, $options);

            foreach ($cursor as $document) {
                $data[] = $this->parseResult($document);
            }
        }

        $dataset = new \MonitoLib\Database\Dataset(
            $total,
            $count,
            $this->getPage(),
            $this->getPerPage(),
            $data
        );

        // \MonitoLib\Dev::pre($count);


        // $options = [
        //     'projection' => $this->fields
        // ];


        // \MonitoLib\Dev::pre($options);


        // \MonitoLib\Dev::pre($dataset);

        return $dataset;
    }
    /**
    * update
    */
    public function update($dto)
    {
        $dtoName   = get_class($dto);
        $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        $model     = new $modelName();
        $table     = $model->getTablename();

        // \MonitoLib\Dev::pre($dto);

        $filter = [
            '_id' => new \MongoDB\BSON\ObjectID($dto->getId())
        ];

        $update = $this->toInsert($dto);

        // \MonitoLib\Dev::pre($update);

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();

        $collection = $handler->$database->$table;
        $result = $collection->updateOne($filter, ['$set' => $update]);

    }
}
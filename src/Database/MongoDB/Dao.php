<?php
namespace MonitoLib\Database\MongoDB;

use \MonitoLib\Database\Connector;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;
use \MonitoLib\Database\Dataset\Dataset;
use \MonitoLib\Database\Dataset\Pagination;
use \MonitoLib\Database\Model;

class Dao extends \MonitoLib\Database\Dao
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2021-06-18
    * fix: database name in insert()
    *
    * 1.0.0 - 2021-03-04
    * initial release
    */

    protected $dbms = 4;
    private $lastId;
    private $dml;

    /**
    * count
    */
    public function count(?bool $onlyFixed = false) : int
    {
        $dml = new Dml($this->model, $this->dbms, $this->getFilter());

        $filter     = $dml->renderFilter($onlyFixed);
        $table      = $this->model->getTablename();
        $collection = $this->getConnection()->$table;

        return $collection->count($filter);
    }
    /**
    * dataset
    */
    public function dataset() : Dataset
    {
        $dml = new Dml($this->model, $this->dbms, $this->getFilter());

        $filter  = $dml->renderFilter();
        $options = $dml->renderOptions();

        $table      = $this->model->getTablename();
        $collection = $this->getConnection()->$table;

        $total = $this->count(true);
        $data  = [];

        if ($total > 0) {
            $count = $this->count();

            if ($count > 0) {
                $cursor = $collection->find($filter, $options);

                foreach ($cursor as $document) {
                    $data[] = $this->_parseResult($document);
                }
            }
        }

        $perPage = $this->getFilter()->getPerPage();
        $page    = $this->getFilter()->getPage();

        if ($perPage <= 0) {
            $perPage = $count;
        }

        // Creates the dataset
        return (new Dataset(
            $data,
            (new Pagination(
                $total,
                $count,
                $page,
                count($data),
                $perPage
            ))
        ));
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
    private function getDml()
    {
        if (is_null($this->dml)) {
            $this->dml = new \MonitoLib\Database\MongoDB\Dml($this->model, $this->dbms, $this->getFilter());
        }

        return $this->dml;
    }
    /**
    * get
    */
    public function get() : ?object
    {
        // $dml = $this->getDml();
        $dml = new \MonitoLib\Database\MongoDB\Dml($this->model, $this->dbms, $this->getFilter());

        $filter  = $dml->renderFilter();
        $options = $dml->renderOptions();

        // \MonitoLib\Dev::pr($filter);

        $table      = $this->model->getTablename();
        $collection = $this->getConnection()->$table;

        // \MonitoLib\Dev::pre($filter);
        $result = $collection->findOne($filter, $options);

        // \MonitoLib\Dev::pre($result);

        if (is_null($result)) {
            return null;
        }

        $this->reset();

        return $this->_parseResult($result);
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
        $table      = $this->model->getTablename();
        $collection = $this->getConnection()->$table;
        $insert     = $this->parseInsert($dto);
        $result     = $collection->insertOne($insert);

        $this->lastId = $result->getInsertedId()->__toString();
        $this->reset();
    }
    /**
    * list
    */
    public function list(?\MonitoLib\Database\Query\Dml $dml = null) : array
    {
        // $dml     = new Dml($this->model, $this->dbms, $this->getFilter());
        $dml = $this->getDml();
        $filter  = $dml->renderFilter();
        $options = $dml->renderOptions();

        // \MonitoLib\Dev::pre($options);


        // $map     = $this->getFilter()->getMap();
        // $map     = $filter->getMap();

        // $map = [
        //     'brand',
        //     'accessory.type',
        //     // 'accessory.installDate',
        //     'accessory.installDate.qqcoisa.vamos.ver.ate.onde.vai',
        //     'accessory.installDate.qqcoisa.vamos.ver.se.da.erro',
        // ];

        // $map = $this->parseMap($map);



        // $_modelName    = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        // $_model        = new $_modelName();
        // $_model        = $this->model;
        // $_modelColumns = array_map(function($e) {
        //     return $e->getId();
        // }, $_model->getColumns());

        // \MonitoLib\Dev::pr($map);
        // \MonitoLib\Dev::pre($_modelColumns);

        // $_mapColumns = array_keys((array)$document);

        // \MonitoLib\Dev::pr($_mapColumns);
        // \MonitoLib\Dev::pre($_modelColumns);

        // $_modelHash = serialize($_modelColumns);
        // $_mapHash   = serialize($_mapColumns);

        // if ($_modelHash !== $_mapHash) {
        //     $dtoName = \MonitoLib\Database\Dto::get($_mapColumns, true);
        // }

        // // \MonitoLib\Dev::pre($dto);

        // $_dto   = new $dtoName();




































        // \MonitoLib\Dev::pre($filter);

        // $sql = $dml->select();
        // $tps = $dml->getTypes();

        // \MonitoLib\Dev::pr($map);
        // $dto = $this->_parseDto(array_values($this->model->getColumnIds()), array_values($map));

        // \MonitoLib\Dev::pre($dto);

        // $stt = $this->parse($sql);
        // $this->execute($stt);

        // Identifica o dto a ser usado

        // \MonitoLib\Dev::pr($filter);
        // \MonitoLib\Dev::pre($options);

        // $dtoName   = $this->dtoName;
        // $modelName = str_replace('\\Dto\\', '\\Model\\', $dtoName);

        // $model     = new $modelName();
        // $table     = $model->getTablename();

        $table      = $this->model->getTablename();
        $collection = $this->getConnection()->$table;

        // $count = $collection->count($filter);
        // $total = $count;
        // $data  = [];


        $cursor = $collection->find($filter, $options);

        foreach ($cursor as $document) {
            // \MonitoLib\Dev::pre($document);
            $data[] = $this->_parseResult($document);
        }

        // $perPage = $this->getPerPage();

        // if ($perPage <= 0) {
        //     $perPage = $count;
        // }

        // $dataset = new \MonitoLib\Database\Dataset(
        //     $total,
        //     $count,
        //     $this->getPage(),
        //     $perPage,
        //     $data
        // );

        // $dataset = [
        //     'data' => $data,
        //     'pagination' => [
        //         'total'   => $total,
        //         'count'   => $count,
        //         'page'    => $this->getPage(),
        //         'perPage' => $perPage,
        //     ]
        // ];

        return $data;
    }
    public function parseDocument(string $dtoName, object $document) : object
    {
        $dml = $this->getDml();
        $dtoName = $dml->getDtoName($dtoName);

        // \MonitoLib\Dev::pr($dtoName);

        $_dto   = new $dtoName['dto']();
        $_model = $dtoName['model'];

        // $_modelName    = str_replace('\\Dto\\', '\\Model\\', $dtoName);
        // $_model        = new $_modelName();
        // $_modelColumns = array_map(function($e) {
        //     return $e->getId();
        // }, $_model->getColumns());
        // $_mapColumns = array_keys((array)$document);

        // \MonitoLib\Dev::pr($_mapColumns);
        // \MonitoLib\Dev::pre($_modelColumns);

        // $_modelHash = serialize($_modelColumns);
        // $_mapHash   = serialize($_mapColumns);

        // if ($_modelHash !== $_mapHash) {
        //     $dtoName = \MonitoLib\Database\Dto::get($_mapColumns, true);
        // }

        // \MonitoLib\Dev::pre($dto);

        // $_model = $this->model;

        // \MonitoLib\Dev::pre($document);

        // Percorre os campos do documento
        foreach ($document as $_key => $_value) {
            if ($_key === '_id') {
                $_key = 'id';
            }

            // \MonitoLib\Dev::vd($_key);

            $_field = $_model->getColumn($_key);

            // if (empty($_field)) {
            //     continue;
            // }

            $__type = $_field->getType() ?? Model::STRING;

            // $__type = 'string';

            // if ($_key === 'invoices') {
            //     \MonitoLib\Dev::e($__type);
                // \MonitoLib\Dev::vde($_value);
                // \MonitoLib\Dev::vd($__type);
            // }

            switch ($__type) {
                case Model::ARRAY:
                    $_value = json_decode(json_encode($_value), true);
                    break;
                case Model::DATE:
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
                    $_value = $date->format('Y-m-d');
                    break;
                case Model::DATETIME:
                    $date = $_value->toDateTime();
                    $date->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
                    $_value = $date->format('Y-m-d H:i:s');
                    break;
                case Model::OID:
                    $_value = $_value->__toString();
                    break;
                default:
                    if (is_array($__type)) {
                        $__type = $__type[0];

                        $_v1 = [];

                        foreach ($_value as $_v) {
                            // \MonitoLib\Dev::pr($_v);
                                // $__type = $dml->getDtoName($_key);
                                // $__type = $__type['dto'];
                                $__type = $_key;
                            $_v1[] = $this->parseDocument($__type, $_v->jsonSerialize());
                        }

                        $_value = $_v1;

                    } else {
                        // \MonitoLib\Dev::vd($_key);

                        // $__type =


                        if (class_exists($__type)) {
                            if (!is_null($_value)) {
                                // $__type = $dml->getDtoName($_key);
                                // $__type = $__type['dto'];
                                // $__type = new $__type();
                                $__type = $_key;

                                // \MonitoLib\Dev::vde($__type);
                                $_value = $this->parseDocument($__type, $_value);
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

        // \MonitoLib\Dev::pr($_dto);

        return $_dto;
    }
    public function parseInsert(object $dto) : array
    {
        // \MonitoLib\Dev::pre($dto);
        $dtoName   = get_class($dto);
        $modelName = str_replace('Dto', 'Model', $dtoName);
        $model     = new $modelName();

        $insert = [];

        // \MonitoLib\Dev::pre($model->getInsertColumnsArray());


        $dml = new \MonitoLib\Database\MongoDB\Dml($model, 4, $this->getFilter());




        // $dml = $this->getDml();
        $insertColumns = $dml->getInsertColumns();

        foreach ($insertColumns as $__column) {
            $__name = $__column->getName();
            $__type = $__column->getType();

            $__var  = Functions::toLowerCamelCase($__name);
            $__get  = 'get' . ucfirst($__var);
            $$__var = $dto->$__get();

            if (is_null($$__var)) {
                continue;
            }

            $__value = '';

            // \MonitoLib\Dev::e($__type);

            // if ($__fn === '_id' || $__fn === 'id') {
            //     continue;
            // }

            // if ($__name === '_id' || $__name === 'id') {
            //     continue;
            // }

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
    public function _parseResult(object $result)
    {
        $index  = 'root';
        $result = $result->jsonSerialize();


        // $dto = $this->dto[$index];


        // $daoName = get_class($this);
        // $dtoName = str_replace('\\Dao\\', '\\Dto\\', $daoName);
        return $this->parseDocument($index, $result);
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

        \MonitoLib\Dev::pre($update);

        $connection = Connector::getInstance()->getConnection($this->connection);
        $database   = $connection->getDatabase();
        $handler    = $connection->getConnection();

        $collection = $handler->$database->$table;
        $result = $collection->updateOne($filter, ['$set' => $update]);

    }
}
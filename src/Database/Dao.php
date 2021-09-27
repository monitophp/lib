<?php

namespace MonitoLib\Database;

use \MonitoLib\App;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;
use \MonitoLib\Database\Query\Dml;

class Dao extends \MonitoLib\Database\Query
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

    const DBMS_MYSQL = 1;
    const DBMS_ORACLE = 2;
    // const DBMS_REST = 1;
    const DBMS_MONGODB = 4;

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
    /**
     *
     */
    public function count(?bool $onlyFixed = false): int
    {
        $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        $sql = $dml->count($onlyFixed);
        // \MonitoLib\Dev::e($sql);
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);
        return +$res[0];
        // return 1;
    }
    /**
     * delete
     */
    public function dataset()
    {
        $dml  = new Dml($this->model, $this->dbms, $this->getFilter());

        $data    = [];
        $total   = $this->count(true);
        $count   = 0;
        $page    = 0;
        $perPage = 0;
        $pages   = 0;

        if ($total > 0) {
            $count = $this->count();

            if ($count > 0) {
                $filter  = $this->getFilter();
                $page    = $filter->getPage();
                $perPage = $filter->getPerPage();
                $pages   = $perPage > 0 ? ceil($count / $perPage) : 1;

                if ($page > $pages) {
                    throw new BadRequest("Número da página atual ($page) maior que o número de páginas ($pages)");
                }

                // Reset $sql
                $this->reset();

                $data = $this->list($dml);
            }
        }

        $dataset = (new \MonitoLib\Database\Dataset\Dataset(
            $data,
            (new \MonitoLib\Database\Dataset\Pagination(
                $total,
                $count,
                $page,
                count($data),
                $perPage
            ))
        ));

        return $dataset;
    }
    /**
     * delete
     */
    public function delete(...$params)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível deletar dados de uma view');
        }

        $keys = $this->model->getPrimaryKeys();

        if (empty($keys)) {
            throw new BadRequest('Model doesn\'t have primary key');
        }

        if (!empty($params)) {
            if (isset($keys[1])) {
                foreach ($params as $param) {
                    if (!is_array($param)) {
                        throw new BadRequest('num e array');
                    }

                    $options  = 0;
                    $sequence = 0;

                    foreach ($param as $p) {
                        if ($sequence === 0) {
                            $options = Query::START_GROUP | Query::OR;
                        }
                        if ($sequence === 1) {
                            $options = Query::END_GROUP;
                        }

                        $name = $keys[$sequence]->getName();
                        $this->equal($name, $p, $options);
                        $sequence++;
                    }
                }
            } else {
                $name = $keys[0]->getName();
                $this->in($name, $params);
            }





            // $this->in('id', [16]);
            // $this->in($name, $p);

            // throw new BadRequest('Não foram informados parâmetros para deletar');
            // } else {
            // $keys = $this->model->getPrimaryKeys();

            // // \MonitoLib\Dev::ee(count($params) . ' !== ' . count($keys));

            // if (count($params) !== count($keys)) {
            //     // throw new BadRequest('Invalid parameters number');
            // }

            // foreach ($params as $p) {
            //     foreach ($keys as $column) {
            //         $name = $column->getName();
            //         \MonitoLib\Dev::e($name . ':' . $p);
            //         $this->in($name, $p);
            //     }
            // }
        }

        // \MonitoLib\Dev::pr($this);

        // \MonitoLib\Dev::pre($this->getFilter());

        $dml = new Dml($this->model, $this->dbms, $this->getFilter());


        $sql = $dml->delete();

        \MonitoLib\Dev::ee($sql);

        $stt = $this->parse($sql);
        $this->execute($stt);

        // Reset filter
        $this->reset();

        // if (oci_num_rows($stt) === 0) {
        // throw new BadRequest('Não foi possível deletar');
        // }
    }
    /**
     *
     */
    public function get(int ...$params)
    {
        if ($this->dbms === self::DBMS_ORACLE) {
            $this->equal('ROWNUM', 1, self::RAW_QUERY);
        }

        // $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        // $sql = $dml->select();
        // // \MonitoLib\Dev::ee($sql);


        // $map = $dml->getMaps();
        // $tps = $dml->getTypes();

        // // \MonitoLib\Dev::pr($map);

        // $stt = $this->parse($sql);


        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();

            if (count($params) !== count($keys)) {
                throw new BadRequest('Invalid parameters number');
            }

            if (count($params) > 1) {
                foreach ($params as $p) {
                    foreach ($keys as $k) {
                        $this->equal($k, $p);
                    }
                }
            } else {
                $this->equal($keys[0], $params[0]);
            }
        }

        $res = $this->list();
        $this->reset();
        return $res->current();
    }
    /**
     *
     */
    public function insert(object $dto)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível inserir registros em uma view');
        }

        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName);
        }

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto);

        // \MonitoLib\Dev::pre($dto);

        // Valida o objeto dto
        $validator = new \MonitoLib\Database\Validator();
        $validator->validate($dto, $this->model);

        // \MonitoLib\Dev::pre($dto);

        // Verifica se existe constraint de chave única
        // $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        // $columns = $this->model->getInsertColumnsArray();
        $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        // $dml = $this->getDml();
        $sql = $dml->insert($dto);
        $stt = $this->parse($sql);
        \MonitoLib\Dev::ee($sql);

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
            $type      = $column->getType();
            $format    = $column->getFormat();
            $transform = $column->getTransform();
            $get       = 'get' . ucfirst($id);
            $value     = $this->escape($dto->$get(), $type);

            $fld .= $name . ',';

            switch ($type) {
                case 'date':
                    $format = $format === 'Y-m-d H:i:s' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                    // $val .= "TO_DATE(:{$name}, '$format'),";
                    $val .= "TO_DATE($value, '$format'),";
                    break;
                default:
                    // $val .= ($transform ?? ':' . $name) . ',';
                    $val .= ($transform ?? $value) . ',';
                    break;
            }
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ($fld) VALUES ($val)";

        // \MonitoLib\Dev::ee($sql);

        // \MonitoLib\Dev::pre($dto);
        // \MonitoLib\Dev::e("$sql\n");

        $stt = $this->parse($sql);

        // foreach ($this->model->getFieldsInsert() as $f) {
        //     $var  = Functions::toLowerCamelCase($f['name']);
        //     $get  = 'get' . ucfirst($var);
        //     $$var = $dto->$get();

        //     @oci_bind_by_name($stt, ':' . $f['name'], $$var);
        // }

        $stt = $this->execute($stt);
    }
    /**
     *
     */
    // public function list(?\MonitoLib\Database\Query\Dml $dml = null) : array
    public function list(?\MonitoLib\Database\Query\Dml $dml = null): \MonitoLib\Database\Dataset\Collection
    {
        if (is_null($dml)) {
            $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        }

        $sql = $dml->select();
        $map = $dml->getMaps();
        $tps = $dml->getTypes();

        // \MonitoLib\Dev::pre($map);
        // \MonitoLib\Dev::ee($sql);

        $stt = $this->parse($sql);

        $this->execute($stt);

        // $columnIds = array_values($this->model->getColumnIds());
        // \MonitoLib\Dev::pre($columnIds);

        // Identifica o dto a ser usado
        $dto = $this->parseDto($this->model->getColumnIds(), array_values($map));

        $collection = new \MonitoLib\Database\Dataset\Collection();
        // $data = [];

        while ($res = $this->fetchArrayAssoc($stt)) {
            $collection->append($this->parseResult(new $dto(), $res));
            // $collection[] = $this->parseResult(new $dto(), $res);
            // $data[] = $this->parseResult(new $dto(), $res);
        }

        // Reset filter
        $this->reset();

        // \MonitoLib\Dev::ee($data[0]);

        // return $data;
        return $collection;
    }
    /**
     * max
     */
    public function max(string $field)
    {
        $sql = "SELECT COALESCE(MAX($field), 0) FROM {$this->model->getTableName()}";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);
        return $res[0];
    }
    /**
     * parseResult
     */
    public function parseResult(object $dto, array $result): object
    {
        $map = $this->getFilter()->getMap();

        // \MonitoLib\Dev::pre($result);

        foreach ($result as $name => $v) {
            $columnId = Functions::toLowerCamelCase($name);
            $column   = $this->model->getColumn($columnId);
            $type     = $column->getType();
            $method   = $map[$columnId] ?? $columnId;
            $format   = $column->getFormat();

            // \MonitoLib\Dev::e($method);

            // \MonitoLib\Dev::e($type);
            // \MonitoLib\Dev::pr($v);

            // \MonitoLib\Dev::pre($column);

            switch ($type) {
                case Model::FLOAT:
                case Model::INT:
                    $v = +$v;
                    break;
                case Model::DATE:
                case Model::DATETIME:
                case Model::TIME:
                    if (!is_null($v)) {
                        $v = new \MonitoLib\Type\DateTime($v);
                    }
            }

            // \MonitoLib\Dev::pr($v);

            $set = 'set' . ucfirst($method);
            $dto->$set($v);
        }

        // \MonitoLib\Dev::pre($dto);

        return $dto;
    }
    /**
     *
     */
    public function truncate()
    {
        $sql = 'TRUNCATE TABLE ' . $this->model->getTableName();
        $stt = $this->parse($sql);
        $this->execute($stt);
    }
    /**
     * update
     */
    public function update(?object $dto = null, ?bool $replace = false)
    {
        if (is_null($dto)) {
            $this->updateMany();
        } else {
            $this->updateOne($dto, $replace);
        }
    }
    /**
     * updateMany
     */
    private function updateMany()
    {
        $filter = $this->getFilter();
        $set    = $filter->getSet();
        // $where  = $filter->getWhere();

        if (empty($set)) {
            throw new BadRequest('É preciso usar o método set() informar os campos que serão atualizados');
        }

        // if (empty($where)) {
        //     throw new BadRequest('Cumé qui tu qué atualizá sem filtro?');
        // }

        $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        $sql = $dml->updateMany();
        \MonitoLib\Dev::ee($sql);
    }
    /**
     * updateOne
     */
    private function updateOne(object $dto, ?bool $replace = false)
    {
        if (!$dto instanceof $this->dtoName) {
            throw new BadRequest('O parâmetro passado não é uma instância de ' . $this->dtoName);
        }

        $filter = $this->getFilter();
        $set    = $filter->getSet();

        if (!empty($set)) {
            throw new BadRequest('Não é possível usar o método set() em conjunto com um objeto dto');
        }

        // Atualiza o objeto com os valores automáticos, caso não informados
        $dto = $this->setAutoValues($dto, true);

        // Valida o objeto dto
        $validator = new \MonitoLib\Database\Validator();
        $validator->validate($dto, $this->model);

        // Verifica se existe constraint de chave única
        // $this->checkUnique($this->model->getUniqueConstraints(), $dto);

        // if (is_null($dml)) {
        $dml = new Dml($this->model, $this->dbms, $this->getFilter());
        // }

        $sql = $dml->update($dto);
        \MonitoLib\Dev::ee($sql);

        $stt = $this->parse($sql);
        $stt = $this->execute($stt);
        return $this->affectedRows();
    }



















































    // public function beginTransaction()
    // {
    //     $this->connection->beginTransaction();
    // }
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
                    $errors[] = "Não foi possível validar a constraint {$uk}";
                }
            }

            if (!empty($errors)) {
                throw new BadRequest('Ocorreu um erro na persistência dos dados', $errors);
            }
        }
    }
    // public function commit()
    // {
    //     $this->getConnection()->commit();
    // }
    /**
     * parseDto
     */
    public function parseDto(array $modelColumns, ?array $mapColumns): string
    {
        $map       = $this->getFilter()->getMap();
        $createDto = false;

        if (!empty($map)) {
            $mapped    = array_map(fn ($e) => $map[$e] ?? $e, $modelColumns);
            $modelHash = serialize(array_values($modelColumns));
            $mapHash   = serialize(array_values($mapped));
            $createDto = $modelHash !== $mapHash;
        }

        if ($createDto) {
            $dto = \MonitoLib\Database\Dto::get($mapColumns, true);
        } else {
            $dto = $this->getDtoName();
        }

        return $dto;
    }
    public function getConnection()
    {
        // \MonitoLib\Dev::vde(\MonitoLib\Database\Connector::getInstance()->getConnection());
        $connection = \MonitoLib\Database\Connector::getInstance()
            ->getConnection($this->connectionName);

        if ($this->dbms === self::DBMS_MONGODB) {
            $database   = $connection->getDatabase();
            $handler    = $connection->getConnection();
            return $handler->$database;
        }

        return $connection->getConnection();
    }
    public function getConnectionInfo()
    {
        // \MonitoLib\Dev::vde(\MonitoLib\Database\Connector::getInstance()->getConnection());
        return \MonitoLib\Database\Connector::getInstance()
            ->getConnection($this->connectionName)
            ->getConnection();
    }
    public function getDtoName()
    {
        if (is_null($this->dtoName)) {
            throw new InternalError('Objeto Dto não informado');
        }

        return $this->dtoName;
    }
    public function getModel()
    {
        // $db = debug_backtrace();

        // \MonitoLib\Dev::pr($db);

        if (is_null($this->model)) {
            throw new InternalError('Objeto Model nulo');
        }

        return $this->model;
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
    // public function rollback()
    // {
    //     $this->connection->rollback();
    // }
    protected function setAutoValues(object $dto, ?bool $updating = false): object
    {
        $columns = $this->model->getColumns();

        if (empty($columns)) {
            throw new InternalError('Campos do modelo não encontrados!');
        }

        // \MonitoLib\Dev::pre($columns);
        // \MonitoLib\Dev::vde($dto);

        foreach ($columns as $column) {
            $id          = ucfirst($column->getId());
            $source      = $column->getSource();
            $sourceParts = explode('.', $source);
            $sourceType  = $sourceParts[0];
            $get         = 'get' . $id;
            $set         = 'set' . $id;
            $value       = $dto->$get();

            if (is_null($value)) {
                $default = $column->getDefault();

                if (in_array($sourceType, ['MAX', 'PARAM', 'SEQUENCE', 'TABLE', 'INSERT', 'UPDATE'])) {
                    $value       = $dto->$get();
                    $primary     = $column->getPrimary();
                    $sourceValue = $sourceParts[1] ?? null;
                    // $auto    = $column->getAuto();


                    switch ($sourceType) {
                        case 'MAX':
                            $value = $this->max($column->getName()) + 1;
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
                            throw new InternalError('Origem de valor inválida');
                    }

                    if ($primary) {
                        $this->lastId = $value;
                    }
                } elseif (!is_null($default)) {

                    switch ($default) {
                        case 'now':
                            $value = new \MonitoLib\Type\DateTime('now');
                            break;
                        case 'userId':
                            $value = App::getUserId();
                            break;
                        default:
                            $value = $default;
                    }
                }

                if (!is_null($value)) {
                    $dto->$set($value);
                }
            }
        }

        return $dto;
    }
}

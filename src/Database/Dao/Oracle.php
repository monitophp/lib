<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Oracle extends Base implements \MonitoLib\Database\Dao
{
    const VERSION = '1.1.1';
    /**
    * 1.1.1 - 2019-12-09
    * fix: minor fixes
    *
    * 1.1.0 - 2019-10-29
    * new: list() now paginates
    * fix: minor fixes
    *
    * 1.0.3 - 2019-05-05
    * fix: date format on update
    *
    * 1.0.2 - 2019-05-03
    * fix: dataset for date fields
    *
    * 1.0.1 - 2019-05-02
    * fix: checks returned value from get function
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */
    protected $dbms = 2;
    protected $lastId;
    private $numRows = 0;



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
        $exe = @oci_execute($stt, $this->executeMode);

        if (!$exe) {
            $e = @oci_error($stt);
            throw new DatabaseError('Ocorreu um erro no banco de dados!', $e);
        }

        return $stt;
    }
    public function fetchArrayAssoc($stt)
    {
        return oci_fetch_array($stt, OCI_ASSOC | OCI_RETURN_NULLS);
    }
    public function fetchArrayNum($stt)
    {
        return $stt->fetch(\PDO::FETCH_NUM);
    }
    public function parse($sql)
    {
        $stt = @oci_parse($this->getConnection(), $sql);

        if (!$stt) {
            $e = @oci_error($stt);
            throw new DatabaseError('Ocorreu um erro no banco de dados!', $e);
        }

        return $stt;
    }
    public function rollback()
    {
        $this->getConnection()->rollback();
    }








    public function count()
    {
        $sql = $this->renderCountSql();
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = oci_fetch_row($stt);

        // Reset filter
        $this->reset();

        return $res[0];
    }
    public function dataset()
    {
        $data   = [];
        $return = [];

        $sqlTotal = $this->renderCountSql(true);
        $sqlCount = $this->renderCountSql();
        $sqlData  = $this->renderSelectSql();

        $stt = $this->connection->parse($sqlTotal);
        $this->connection->execute($stt);
        $res = oci_fetch_row($stt);
        $total = $res[0];
        $return['total'] = +$total;

        if ($total > 0) {
            $stt = $this->connection->parse($sqlCount);
            $this->connection->execute($stt);

            $res = oci_fetch_row($stt);
            $count = $res[0];
            $return['count'] = +$count;

            if ($count > 0) {
                $page    = $this->getPage();
                $perPage = $this->getPerPage();
                $pages   = $perPage > 0 ? ceil($count / $perPage) : 1;

                if ($page > $pages) {
                    throw new BadRequest("Número da página atual ($page) maior que o número de páginas ($pages)!");
                }

                if ($perPage > 0) {
                    $startRow = (($page - 1) * $perPage) + 1;
                    $endRow   = $perPage * $page;
                    $sqlData  = "SELECT {$this->getSelectFields(false)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sqlData) a) WHERE rown_ BETWEEN $startRow AND $endRow";
                }

                // Reset $sql
                $this->reset();

                $data = $this->setSql($sqlData)->list();
                $return['data']  = $data;
                $return['page']  = +$page;
                $return['pages'] = +$pages;
            }
        }

        return $return;
    }
    public function delete(...$params)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequest('Não é possível deletar dados de uma view!');
        }

        $sql = $this->renderDeleteSql();
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);

        // Reset filter
        $this->reset();

        if (oci_num_rows($stt) === 0) {
            throw new BadRequest('Não foi possível deletar!');
        }
    }
    public function get($sql = null)
    {
        $res = $this->list($sql);
        $this->reset();
        return isset($res[0]) ? $res[0] : null;
    }
    public function getById(...$params)
    {
        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();

            if (count($params) !== count($keys)) {
                throw new BadRequest('Invalid parameters number!');
            }

            if (count($params) > 1) {
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
    public function getLastId()
    {
        return $this->lastId;
    }
    public function insert($dto)
    {
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
            $fld .= $f['name'] . ',';

            switch ($f['type']) {
                case 'date':
                    $format = $f['format'] === 'Y-m-d H:i:s' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                    $val .= "TO_DATE(:{$f['name']}, '$format'),";
                    break;
                default:
                    $val .= ($f['transform'] ?? ':' . $f['name']) . ',';
                    break;
            }
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ($fld) VALUES ($val)";
        $stt = $this->connection->parse($sql);

        foreach ($this->model->getFieldsInsert() as $f) {
            $var  = Functions::toLowerCamelCase($f['name']);
            $get  = 'get' . ucfirst($var);
            $$var = $dto->$get();

            @oci_bind_by_name($stt, ':' . $f['name'], $$var);
        }

        $this->connection->execute($stt);
    }
    public function list($sql = null)
    {
        if (is_null($sql)) {
            $sql = $this->renderSelectSql();
        }

        $page    = $this->getPage();
        $perPage = $this->getPerPage();

        if ($perPage > 0) {
            $startRow = (($page - 1) * $perPage) + 1;
            $endRow   = $perPage * $page;
            $sql      = "SELECT {$this->getSelectFields(false)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sql) a) WHERE rown_ BETWEEN $startRow AND $endRow";
        }

        $stt = $this->parse($sql);
        $this->execute($stt);

        $data = [];

        while ($res = $this->fetchArrayAssoc($stt)) {
            $data[] = $this->getValue($res);
        }

        $stt = null;

        return $data;
    }
    public function nextValue($sequence)
    {
        $sql = "SELECT $sequence.nextval FROM dual";
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);
        return $res[0];
    }
    public function paramValue($tableName, $param, $nextValue = true)
    {
        $nvl = $nextValue ? 1 : 0;
        $sql = "SELECT NVL($param, $nvl) FROM {$tableName} FOR UPDATE";
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);
        $res = $this->connection->fetchArrayNum($stt);

        $value = $res[0];
        $newValue = $value;

        if (!$nextValue) {
            $value++;
        }

        $newValue++;

        $sql = "UPDATE $tableName SET $param = $newValue";
        $stt = $this->connection->parse($sql);
        $this->connection->execute($stt);

        return $value;
    }
    public function procedure($name, ...$params)
    {

    }
    public function update($dto)
    {
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
                $key .= "$name = :$name AND ";
            } else {
                switch ($f['type']) {
                    case 'date':
                        $format = $f['format'] === 'Y-m-d H:i:s' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                        $fld .= "$name = TO_DATE(:{$f['name']}, '$format'),";
                        break;
                    default:
                        $fld .= "$name = " . ($f['transform'] ?? ":$name") . ',';
                        break;
                }
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);

        $sql = 'UPDATE ' . $this->model->getTableName() . " SET $fld WHERE $key";
        $stt = $this->connection->parse($sql);

        foreach ($this->model->getFields() as $f) {
            $var  = Functions::toLowerCamelCase($f['name']);
            $get  = 'get' . ucfirst($var);
            $$var = $dto->$get();

            @oci_bind_by_name($stt, ':' . $f['name'], $$var);
        }

        $stt = $this->connection->execute($stt);

        if (oci_num_rows($stt) === 0) {
            throw new BadRequest('Não foi possível atualizar!');
        }
    }
}
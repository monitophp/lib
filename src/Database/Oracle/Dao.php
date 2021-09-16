<?php
namespace MonitoLib\Database\Oracle;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\DatabaseError;
use \MonitoLib\Functions;
use \MonitoLib\Database\Query\Dml;
use \MonitoLib\Database\Query;

class Dao extends \MonitoLib\Database\Dao // implements \MonitoLib\Database\Dao
{
    const VERSION = '1.2.1';
    /**
    * 1.2.1 - 2020-09-18
    * new: minor changes
    *
    * 1.2.0 - 2020-05-19
    * fix: minor fixes
    *
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
    private $executeMode;
    private $dml;
    protected $lastId;
    private $affectedRows = 0;

    public function __construct()
    {
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
        parent::__construct();
    }
    public function beginTransaction()
    {
        $this->executeMode = OCI_NO_AUTO_COMMIT;
    }
    public function commit()
    {
        @oci_commit($this->getConnection());
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }
    public function execute($stt)
    {
        $exe = @oci_execute($stt, $this->executeMode);

        if (!$exe) {
            $e = @oci_error($stt);
            throw new DatabaseError('Ocorreu um erro no banco de dados', $e);
        }

        return $stt;
    }
    public function fetchAll($stt)
    {
        oci_fetch_all($stt, $res, null, null, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
        return $res;
    }
    public function fetchArrayAssoc($stt)
    {
        return oci_fetch_array($stt, OCI_ASSOC | OCI_RETURN_NULLS);
    }
    public function fetchArrayNum($stt)
    {
        return oci_fetch_array($stt, OCI_NUM | OCI_RETURN_NULLS);
    }
    public function OLDget(...$params)
    {
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
        return isset($res[0]) ? $res[0] : null;
    }
    public function getById(...$params)
    {
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

            return $this->get();
        }
    }
    public function getLastId()
    {
        return $this->lastId;
    }
    public function insert($dto) : void
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
        $dml = $this->getDml();
        $sql = $dml->insert($dto);
        $stt = $this->parse($sql);
        // \MonitoLib\Dev::ee($sql);

        // foreach ($columns as $column) {
        //     $id        = $column->getId();
        //     $name      = $column->getName();
        //     $transform = $column->getTransform();
        //     $var       = Functions::toLowerCamelCase($name);
        //     $get       = 'get' . ucfirst($id);
        //     $$id       = $dto->$get();

        //     $stt->bindParam(':' . $name, $$id);
        // }

        $this->execute($stt);
        $this->reset();
    }
    private function getDml()
    {
        if (is_null($this->dml)) {
            $this->dml = new \MonitoLib\Database\Query\Dml($this->model, $this->dbms, $this->getFilter());
        }

        return $this->dml;
    }
    public function nextValue($sequence)
    {
        $sql = "SELECT $sequence.nextval FROM dual";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);
        return $res[0];
    }
    public function paramValue($tableName, $param, $nextValue = true)
    {
        $nvl = $nextValue ? 1 : 0;
        $sql = "SELECT NVL($param, $nvl) FROM {$tableName} FOR UPDATE";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);

        $value = $res[0];
        $newValue = $value;

        if (!$nextValue) {
            $value++;
        }

        $newValue++;

        $sql = "UPDATE $tableName SET $param = $newValue";
        $stt = $this->parse($sql);
        $this->execute($stt);

        return $value;
    }
    public function parse($sql)
    {
        $stt = @oci_parse($this->getConnection(), $sql);

        if (!$stt) {
            $e = @oci_error($stt);
            throw new DatabaseError('Ocorreu um erro no banco de dados', $e);
        }

        return $stt;
    }
    public function procedure($name, ...$params)
    {
        $params = array_map(function($item){
            return is_null($item) ? 'NULL' : (is_numeric($item) ? $item : "'$item'");
        }, $params);

        $prt = explode('.', $name);
        $pkg = strtoupper($prt[0]);
        $prc = strtoupper($prt[1]) ?? null;

        $sql = 'SELECT argument_name, in_out FROM USER_ARGUMENTS '
            . "WHERE object_name = UPPER('$prc') ";

        if (!is_null($pkg)) {
            $sql .= "AND package_name = UPPER('$pkg') ";
        }

        $sql .= 'ORDER BY sequence';

        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchAll($stt);

        $arg   = [];

        foreach ($res as $r) {
            $arg[] = $r['ARGUMENT_NAME'];
        }

        $prm = implode(',:', $arg);
        $sql = "BEGIN $name(:$prm);END;";
        $stt = $this->parse($sql);

        $index = 0;
        $out   = [];
        foreach ($res as $r) {
            $a = $r['ARGUMENT_NAME'];
            $t = $r['IN_OUT'];
            $arg[] = $a;

            if ($t === 'IN') {
                $v = $params[$index];
                @oci_bind_by_name($stt, ':' . $a, $params[$index]);
            } else {
                $out[] = $a;
                @oci_bind_by_name($stt, ':' . $a, $$a, 255);
            }
            $index++;
        }

        $this->execute($stt);

        if (!empty($out)) {
            $res = [];

            foreach ($out as $o) {
                $res[$o] = $$o;
            }

            return $res;
        }
    }
    public function rollback()
    {
        @oci_rollback($this->getConnection());
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }
}
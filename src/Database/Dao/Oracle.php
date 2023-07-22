<?php
/**
 * Database\Dao\Oracle.
 *
 * @version 1.3.0
 */

namespace MonitoLib\Database\Dao;

use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\DatabaseErrorException;

class Oracle extends \MonitoLib\Database\Query\Sql
{
    protected $dbms = 2;
    protected $lastId;
    private $executeMode;

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
        @oci_commit($this->getConnection($this->connection));
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }

    public function count()
    {
        $sql = $this->renderCountSql();
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = oci_fetch_row($stt);
        $this->reset();

        return $res[0];
    }

    public function dataset()
    {
        $data = [];
        $return = [];

        $perPage = $this->getPerPage();
        $sqlTotal = $this->renderCountSql(true);
        $sqlCount = $this->renderCountSql();
        $sqlData = $this->renderSelectSql();

        $stt = $this->parse($sqlTotal);
        $this->execute($stt);
        $res = oci_fetch_row($stt);
        $total = $res[0];
        $return['total'] = +$total;

        if ($total > 0) {
            $stt = $this->parse($sqlCount);
            $this->execute($stt);

            $res = oci_fetch_row($stt);
            $count = $res[0];
            $return['count'] = +$count;

            if ($count > 0) {
                $page = $this->getPage();
                $pages = $perPage > 0 ? ceil($count / $perPage) : 1;

                if ($page > $pages) {
                    throw new BadRequestException("Current page ({$page}) is bigger than total pages ({$pages})");
                }

                $this->reset();

                $data = $this->list($sqlData);
                $return['data'] = $data;
                $return['page'] = +$page;
                $return['pages'] = +$pages;
            }
        }

        return $return;
    }

    public function delete(...$params)
    {
        if ($this->model->getTableType() === 'view') {
            throw new BadRequestException('Cannot delete from a view');
        }

        $keys = $this->model->getPrimaryKeys();

        if (count($params) !== count($keys)) {
            throw new BadRequestException('Invalid parameters number');
        }

        foreach ($params as $p) {
            foreach ($keys as $k) {
                $this->equal($k, $p);
            }
        }

        $sql = $this->renderDeleteSql();
        $stt = $this->parse($sql);
        $this->execute($stt);
        $this->reset();
    }

    public function execute($stt)
    {
        $exe = @oci_execute($stt, $this->executeMode);

        if (!$exe) {
            $e = @oci_error($stt);

            throw new DatabaseErrorException('Database error has occurred', $e);
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

    public function get($sql = null)
    {
        $res = $this->list($sql);
        $this->reset();

        return $res[0] ?? null;
    }

    public function getById(...$params)
    {
        if (!empty($params)) {
            $keys = $this->model->getPrimaryKeys();

            if (count($params) !== count($keys)) {
                throw new BadRequestException('Invalid parameters number');
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

    public function insert($dto)
    {
        if (!$dto instanceof $this->dtoName) {
            throw new BadRequestException('Parameter is not an instance of ' . $this->dtoName);
        }

        $this->model->validate($dto);

        $dto = $this->setAutoValues($dto);

        $fld = '';
        $val = '';

        foreach ($this->model->getFieldsInsert() as $f) {
            $fld .= $f['name'] . ',';

            switch ($f['type']) {
                case 'date':
                    $format = $f['format'] === 'Y-m-d H:i:s' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                    $val .= "TO_DATE(:{$f['name']}, '{$format}'),";
                    break;

                default:
                    $val .= ($f['transform'] ?? ':' . $f['name']) . ',';
            }
        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ({$fld}) VALUES ({$val})";
        $stt = $this->parse($sql);

        foreach ($this->model->getFieldsInsert() as $f) {
            $var = to_lower_camel_case($f['name']);
            $get = 'get' . ucfirst($var);
            ${$var} = $dto->{$get}();

            @oci_bind_by_name($stt, ':' . $f['name'], ${$var});
        }

        $stt = $this->execute($stt);
    }

    public function list(?string $sql = null): array
    {
        if (is_null($sql)) {
            $sql = $this->renderSelectSql();
        }

        $stt = $this->parse($sql);
        $this->execute($stt);

        $data = [];

        while ($res = $this->fetchArrayAssoc($stt)) {
            $data[] = $this->getValue($res);
        }

        $stt = null;
        $this->reset();

        return $data;
    }

    public function nextValue($sequence)
    {
        $sql = "SELECT {$sequence}.nextval FROM dual";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);

        return $res[0];
    }

    public function paramValue($tableName, $param, $nextValue = true)
    {
        $nvl = $nextValue ? 1 : 0;
        $sql = "SELECT NVL({$param}, {$nvl}) FROM {$tableName} FOR UPDATE";
        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchArrayNum($stt);

        $value = $res[0];
        $newValue = $value;

        if (!$nextValue) {
            ++$value;
        }

        ++$newValue;

        $sql = "UPDATE {$tableName} SET {$param} = {$newValue}";
        $stt = $this->parse($sql);
        $this->execute($stt);

        return $value;
    }

    public function parse($sql)
    {
        $stt = @oci_parse($this->getConnection($this->connection), $sql);

        if (!$stt) {
            $e = @oci_error($stt);

            throw new DatabaseErrorException('Database error has occurred', $e);
        }

        return $stt;
    }

    public function procedure($name, ...$params)
    {
        $params = array_map(function ($item) {
            return is_null($item) ? 'NULL' : (is_numeric($item) ? $item : "'{$item}'");
        }, $params);

        $prt = explode('.', $name);
        $pkg = strtoupper($prt[0]);
        $prc = strtoupper($prt[1]) ?? null;

        $sql = 'SELECT argument_name, in_out FROM USER_ARGUMENTS '
            . "WHERE object_name = UPPER('{$prc}') ";

        if (!is_null($pkg)) {
            $sql .= "AND package_name = UPPER('{$pkg}') ";
        }

        $sql .= 'ORDER BY sequence';

        $stt = $this->parse($sql);
        $this->execute($stt);
        $res = $this->fetchAll($stt);

        $arg = [];

        foreach ($res as $r) {
            $arg[] = $r['ARGUMENT_NAME'];
        }

        $prm = implode(',:', $arg);
        $sql = "BEGIN {$name}(:{$prm});END;";
        $stt = $this->parse($sql);

        $index = 0;
        $out = [];

        foreach ($res as $r) {
            $a = $r['ARGUMENT_NAME'];
            $t = $r['IN_OUT'];
            $arg[] = $a;

            if ('IN' === $t) {
                $v = $params[$index];
                @oci_bind_by_name($stt, ':' . $a, $params[$index]);
            } else {
                $out[] = $a;
                @oci_bind_by_name($stt, ':' . $a, ${$a}, 255);
            }
            ++$index;
        }

        $this->execute($stt);

        if (!empty($out)) {
            $res = [];

            foreach ($out as $o) {
                $res[$o] = ${$o};
            }

            return $res;
        }
    }

    public function rollback()
    {
        @oci_rollback($this->getConnection($this->connection));
        $this->executeMode = OCI_COMMIT_ON_SUCCESS;
    }

    public function update($dto)
    {
        if (!$dto instanceof $this->dtoName) {
            throw new BadRequestException('Parameter is not an instance of ' . $this->dtoName);
        }

        $this->model->validate($dto);

        $dto = $this->setAutoValues($dto, true);

        $key = '';
        $fld = '';

        foreach ($this->model->getFields() as $f) {
            $name = $f['name'];

            if ($f['primary']) {
                $key .= "{$name} = :{$name} AND ";
            } else {
                switch ($f['type']) {
                    case 'date':
                        $format = 'Y-m-d H:i:s' === $f['format'] ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                        $fld .= "{$name} = TO_DATE(:{$f['name']}, '{$format}'),";
                        break;

                    default:
                        $fld .= "{$name} = " . ($f['transform'] ?? ":{$name}") . ',';
                }
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);

        $sql = 'UPDATE ' . $this->model->getTableName() . " SET {$fld} WHERE {$key}";
        $stt = $this->parse($sql);

        foreach ($this->model->getFields() as $f) {
            $var = to_lower_camel_case($f['name']);
            $get = 'get' . ucfirst($var);
            ${$var} = $dto->{$get}();

            @oci_bind_by_name($stt, ':' . $f['name'], ${$var});
        }

        $stt = $this->execute($stt);

        if (oci_num_rows($stt) === 0) {
            throw new BadRequestException('Update failed');
        }
    }
}

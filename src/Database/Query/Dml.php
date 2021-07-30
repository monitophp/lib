<?php
namespace MonitoLib\Database\Query;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use MonitoLib\Exception\NotFound;
use \MonitoLib\Functions;
use \MonitoLib\Validator;
use \MonitoLib\Database\Dao;
use \MonitoLib\Database\Query\Filter;

class Dml
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2019-07-09
    * Initial release
    */
    private $dbms;
    private $filter;
    private $model;
    private $dao;
    private $whereString = '';
    private $fixedWhereString;
    private $maps = [];
    private $types = [];
    private $selectSql;

    public function setSelectSql(string $sql) : self
    {
        $this->selectSql = $sql;
        return $this;
    }
    public function __construct(\MonitoLib\Database\Model $model, int $dbms, Filter $filter)
    {
        $this->model  = $model;
        $this->dbms   = $dbms;
        $this->filter = $filter;
    }
    public function count(bool $all = false) : string
    {
        return 'SELECT COUNT(*) FROM ' . $this->model->getTableName() . $this->where($all);
    }
    public function delete() : string
    {
        return 'DELETE FROM ' . $this->model->getTableName() . $this->where();
    }
    private function escape(string $value, \MonitoLib\Database\Model\Column $column) : string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        $value = str_replace(["'"],["''"], $value);

        $type = $column->getType();

        switch ($type) {
            case 'double':
            case 'float':
            case 'int':
                return $value;
            default:
                return "'$value'";
        }
    }
    public function getColumn(string $columnName, bool $isRaw) : \MonitoLib\Database\Model\Column
    {
        if ($isRaw) {
            return (new \MonitoLib\Database\Model\Column())->setName($columnName);
        }

        $model   = $this->model;
        $columns = array_filter($model->getColumns(), function($e) use ($columnName) {
            return $e->getName() === $columnName;
        });

        if (empty($columns)) {
            throw new NotFound("Column $columnName doesn't belong to " . get_class($model));
        }

        return reset($columns);
    }
    public function getMaps() : array
    {
        return $this->maps;
    }
    public function getTypes() : array
    {
        return $this->types;
    }
    public function insert(object $dto) : string
    {
        // \MonitoLib\Dev::pre($columns);

        $columns = $this->model->getColumns();

        $fld = '';
        $val = '';
        $delimiter = $this->dbms === Dao::DBMS_MYSQL ? '`' : '';

        foreach ($columns as $column) {
            $id        = $column->getId();
            $name      = $column->getName();
            $transform = $column->getTransform();
            $get       = 'get' . ucfirst($id);
            $value     = $dto->$get();

            if (!is_null($value)) {
                $value     = $this->escape($value, $column);
                $fld .= "{$delimiter}{$name}{$delimiter},";
                // $val .= ($transform ?? ':' . $name) . ',';
                $val .= $value . ',';
            }

        }

        $fld = substr($fld, 0, -1);
        $val = substr($val, 0, -1);

        $sql = 'INSERT INTO ' . $this->model->getTableName() . " ($fld) VALUES ($val)";
        return $sql;
    }
    private function renderFieldsSql() : string
    {
        // bool $format = true, bool $aliases = false

        $model   = $this->model;
        $filter  = $this->filter;
        $map     = $filter->getMap();
        $columns = $filter->getColumns();

        if (empty($columns)) {
            $columns = $model->getColumns();
        } else {
            $columns = array_map(function($e) use ($model) {
                return $model->getColumn($e);
            }, $columns);
        }

        // \MonitoLib\Dev::pre($columns);

        $list = '';
        // $selected = $this->selectedFields;

        // if (empty($selected)) {
        //     $selected = $this->getModelFields();
        // }

        foreach ($columns as $column) {
            $id     = $column->getId();
            $name   = $column->getName();
            $type   = $column->getType();
            $format = $column->getFormat();
            $alias  = $column->getAlias();
            // $alias  = $column->map($name) ?? null;
            // $alias = null;

            // if ($format && $type === 'date' && $this->dbms === 2) {
            if ($type === 'datetime' && $this->dbms === 2) {
                $mask = 'YYYY-MM-DD' . ($format === 'Y-m-d H:i:s' ? ' HH24:MI:SS' : '');
                $alias ??= $name;
                $name = "TO_CHAR($name, '$mask')";
            // } else {
            //     // if ($aliases && !is_null($alias)) {
            //     if (!is_null($alias)) {
            //         $name = $alias;
            //     } else {
            //         $name .= is_null($alias) ? '' : " AS $alias";
            //     }
            }

            switch ($type) {
                case 'double':
                case 'float':
                case 'int':
                    $type = 'n';
                    break;
                case 'bool':
                    $type = 'b';
                    break;
                default:
                    $type = 's';
            }

            $mapValue = $map[$id] ?? $alias ?? $id ?? $name;

            if (is_null($alias)) {
                $this->maps[$name] = $mapValue;
                $this->types[$name] = $type;
            } else {
                $this->maps[$alias] = $mapValue;
                $this->types[$alias] = $type;
                $name .= " AS $alias";
            }

            $list .= "$name, ";
        }

        $list = substr($list, 0, -2);

        // \MonitoLib\Dev::pr($xColumns);
        // \MonitoLib\Dev::ee($list);

        return $list;
    }
    private function limit() : string
    {
        $filter  = $this->filter;
        $sql     = '';
        $page    = $filter->getPage();
        $perPage = $filter->getPerPage();

        if ($perPage > 0 && $this->dbms == 1) {
            $sql .= ' LIMIT ' . (($page - 1) * $perPage) . ',' . $perPage;
        }

        return $sql;
    }
    private function orderBy() : string
    {
        $sql     = '';
        $filter  = $this->filter;
        $orderBy = $filter->getOrderBy();

        if (!empty($orderBy)) {
            $sql = ' ORDER BY ';

            foreach ($orderBy as $column => $direction) {
                $sql .= $column . ' ' . ($direction === '' ? '' : strtoupper($direction)) . ', ';
            }

            $sql = substr($sql, 0, -2);
        }

        return $sql;
    }
    private function parseWhere($where) : string
    {
        $name       = $where->getColumn();
        $comparison = $where->getComparison();
        $value      = $where->getValue();
        $options    = $where->getOptions();
        $operator   = $options->getOperator();
        // TODO: validar start/end group
        $startGroup = $options->startGroup() ? '(' : '';
        $endGroup   = $options->endGroup() ? ')' : '';
        $isRaw      = $options->isRaw();
        $column     = $this->getColumn($name, $isRaw);

        if ($comparison === Filter::BETWEEN) {
            $value = $this->escape($value[0], $column) . ' AND ' . $this->escape($value[1], $column);
        }

        if (is_array($value)) {
            $value = '(' . implode(',', array_map(function($e) use ($column) {
                return $this->escape($e, $column);
            }, $value)) . ')';
        } else {
            $value = $this->escape($value, $column);
        }

        return " {$operator} {$startGroup}{$name} {$comparison} {$value}{$endGroup}";
    }
    public function select(?bool $validate = true) : string
    {
        // \MonitoLib\Dev::pre($query);

        // $sql = $this->sql;

        // if (is_null($sql)) {
            // if ($this->selectSqlReady) {
                // return $this->getSelectSql();
            // }
            $sql = 'SELECT '
                . $this->renderFieldsSql()
                . ' FROM '
                . $this->model->getTableName()
                . $this->where()
                . $this->orderBy()
                ;

        if ($this->dbms === Dao::DBMS_ORACLE) {
            $filter   = $this->filter;
            $page     = $filter->getPage();
            $perPage  = $filter->getPerPage();
            if ($perPage > 0) {
                $startRow = (($page - 1) * $perPage) + 1;
                $endRow   = $perPage * $page;
                $sql      = "SELECT {$this->renderFieldsSql(false)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sql) a) WHERE rown_ BETWEEN $startRow AND $endRow";
            }
        } else {
            $sql .= $this->limit();
        }

        // }
        // \MonitoLib\Dev::ee($sql);

        // $page    = $this->getPage();
        // $perPage = $this->getPerPage();

        // if ($this->dbms === 2 && $perPage > 0) {
        //     $startRow = (($page - 1) * $perPage) + 1;
        //     $endRow   = $perPage * $page;
        //     $sql      = "SELECT {$this->renderFieldsSql(false, true)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sql) a) WHERE rown_ BETWEEN $startRow AND $endRow";
        // }

        return $sql;
    }
    public function update(array $columns, object $dto) : string
    {
        $key = '';
        $fld = '';

        $columns = $this->model->getColumns();
        // \MonitoLib\Dev::vde($columns);

        foreach ($columns as $column) {
            $id        = $column->getId();
            $name      = $column->getName();
            $type      = $column->getType();
            $primary   = $column->getPrimary();
            $format    = $column->getFormat();
            $transform = $column->getTransform();
            $get       = 'get' . ucfirst($id);
            $value     = $this->escape($dto->$get(), $column);

        // foreach ($this->model->getFields() as $f) {
            // $name = $f['name'];

            if ($primary) {
                $key .= "$name = $value AND ";
            } else {
                // if ($this->dbms === Dao::DBMS_ORACLE && )
                switch ($type) {
                    case 'date':
                        $format = $format === 'Y-m-d H:i:s' ? 'YYYY-MM-DD HH24:MI:SS' : 'YYYY-MM-DD';
                        $fld .= "$name = TO_DATE($value, '$format'),";
                        break;
                    default:
                        $fld .= "$name = " . ($transform ?? "$value") . ',';
                        break;
                }
            }
        }

        $key = substr($key, 0, -5);
        $fld = substr($fld, 0, -1);

        $sql = 'UPDATE '
            . $this->model->getTableName()
            . ' SET '
            . $fld
            . ' WHERE '
            . $key;
        return $sql;
    }
    private function where(?bool $all = false) : string
    {
        if ($this->whereString === '') {
            $filter    = $this->filter;
            $whereList = $filter->getWhere();

            if (!empty($whereList)) {
                foreach ($whereList as $where) {
                    $this->whereString .= $this->parseWhere($where);
                }

                $this->whereString = ' WHERE' . preg_replace('/^ (AND|OR)/', '', $this->whereString);
            }
        }

        return $this->whereString;
    }
}
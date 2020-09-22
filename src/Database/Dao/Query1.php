<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class Query
{
    const VERSION = '1.3.0';
    /**
    * 1.3.0 - 2019-12-09
    * new: andNotIn
    * fix: several fixes
    *
    * 1.2.0 - 2019-10-23
    * fix: several fixes
    *
    * 1.1.3 - 2019-06-05
    * fix: removed checkIfFieldExists from setQuery
    *
    * 1.1.2 - 2019-05-05
    * fix: getSelectFields parameter on dataset method
    * fix: checkIfFieldExists in all query methods
    *
    * 1.1.1 - 2019-05-03
    * new: getSelectFields checks format
    *
    * 1.1.0 - 2019-05-02
    * new: removed parseRequest
    * fix: CHECK_NULL constant name
    * 
    * 1.0.0 - 2019-04-07
    * First versioned
    */

    const FIXED_QUERY = 1;
    const CHECK_NULL  = 2;
    const RAW_QUERY   = 4;

    const DB_MYSQL  = 1;
    const DB_ORACLE = 2;

    private $criteria;
    private $fixedCriteria;
    private $reseted = false;

    private $selectedFields;

    private $page    = 1;
    private $perPage = 0;
    private $orderBy = [];
    private $sql;
    private $sqlCount;

    private $map = [];
    protected $convertName = true;

    private $selectSql;
    private $selectSqlReady = false;
    private $countSql;
    private $countSqlReady = false;
    private $orderBySql;
    private $orderBySqlReady = false;

    private $modelFields;

    public function andIn($field, $values, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);

        if (empty($values)) {
            throw new BadRequest('Valores inválidos!');
        }

        $value = '';

        foreach ($values as $v) {
            if (is_numeric($v)) {
                $value .= $v;
            } else {
                $value .= "'" . $this->escape($v) . "'";
            }

            $value .= ',';
        }

        $value = substr($value, 0, -1);

        $sql = "{$field['name']} IN ($value) AND ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function orIn($field, $values, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);

        if (empty($values)) {
            throw new BadRequest('Valores inválidos!');
        }

        $value = '';

        foreach ($values as $v) {
            if (is_numeric($v)) {
                $value .= $v;
            } else {
                $value .= "'" . $this->escape($v) . "'";
            }

            $value .= ',';
        }

        $value = substr($value, 0, -1);

        $sql = "{$field['name']} IN ($value) OR ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function andNotIn($field, $values, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);

        if (empty($values)) {
            throw new BadRequest('Valores inválidos!');
        }

        $value = '';

        foreach ($values as $v) {
            if (is_numeric($v)) {
                $value .= $v;
            } else {
                $value .= "'" . $this->escape($v) . "'";
            }

            $value .= ',';
        }

        $value = substr($value, 0, -1);

        $sql = "{$field['name']} NOT IN ($value) AND ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function startGroup($modifiers = 0)
    {
        $sql = '(';

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function startAndGroup($modifiers = 0)
    {
        if (preg_match('/ (AND|OR) $/', $this->criteria, $m)) {
            $this->criteria = substr($this->criteria, 0, strlen($m[0]) * -1);
        }

        $sql = ' AND (';

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function startOrGroup($modifiers = 0)
    {
        if (preg_match('/ (AND|OR) $/', $this->criteria, $m)) {
            $this->criteria = substr($this->criteria, 0, strlen($m[0]) * -1);
        }

        $sql = ' OR (';

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function endGroup($modifiers = 0)
    {
        $sql = ')';

        if (preg_match('/ (AND|OR) $/', $this->criteria, $m)) {
            $this->criteria = substr($this->criteria, 0, strlen($m[0]) * -1);
        }

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    private function addCriteriaParser($logicalOperator, $comparisonOperator, $field, $value, $modifiers = 0)
    {
        $f = $this->checkIfFieldExists($field, $modifiers);
        $type   = $f['type'];
        $name   = $f['name'];

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;
        $null  = ($modifiers & self::CHECK_NULL) === self::CHECK_NULL;
        $raw   = ($modifiers & self::RAW_QUERY) === self::RAW_QUERY;

        if (is_null($value) && $null) {
            return $this->andIsNull($field, $fixed);
        }

        if ($this->dbms === 2 && $type === 'date') {
            $format = $f['format'];

            if (!Validator::date($value, 'Y-m-d') && !Validator::date($value, 'Y-m-d H:i:s')) {
                throw new BadRequest('Data inválida: ' . $value);
            }

            $f = 'YYYY-MM-DD HH24:MI:SS';

            if ($format === 'Y-m-d H:i:s' && Validator::date($value, 'Y-m-d')) {
                $name = "TRUNC($field)";
            }

            $value = "TO_DATE('$value', '$f')";
            $raw = true;
        }

        if ($raw || $type === 'int') {
            $q = '';
        } else {
            $q = '\'';
        }

        $sql = "$name $comparisonOperator $q" . ($raw ? $value : $this->escape($value)) . "$q $logicalOperator ";

        if (substr($this->criteria, -1) === ')') {
            $this->criteria .= " $logicalOperator ";
        }

        $this->criteria .= $sql;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function andEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '=', $field, $value, $modifiers);
        return $this;
    }
    public function andGreaterEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '>=', $field, $value, $modifiers);
        return $this;
    }
    public function andGreaterThan($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '>', $field, $value, $modifiers);
        return $this;
    }
    public function andIsNull($field, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);
        $sql = "{$field['name']} IS null AND ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function andBitAnd($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '&', $field, $value, $modifiers);
        return $this;
    }
    public function andLessEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '<=', $field, $value, $modifiers);
        return $this;
    }
    public function andLessThan($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '<', $field, $value, $modifiers);
        return $this;
    }
    public function andNotEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', '<>', $field, $value, $modifiers);
        return $this;
    }
    public function andIsNotNull($field, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);
        $sql = "{$field['name']} IS NOT null AND ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function andLike($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', 'LIKE', $field, $value, $modifiers);
        return $this;
    }
    public function andNotLike($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('AND', 'NOT LIKE', $field, $value, $modifiers);
        return $this;
    }
    public function andFilter($field, $value, $type = 'string')
    {
        $f = $this->checkIfFieldExists($field);
        $type = $f['type'];
        $format = $f['format'];

        switch ($type) {
            case 'date':
            case 'double':
            case 'int':
                $value = urldecode($value);

                // Verifica se é intervalo
                if (preg_match('/^([0-9.]+)-([0-9.]+)$/', $value, $m)) {
                    $this->criteria .= "$field BETWEEN $m[1] AND $m[2] AND ";
                    break;
                }

                // Verifica se tem algum modificador
                if (preg_match('/^([><=!]{1,2})?([0-9.\-\s:]+)$/', $value, $m)) {
                    switch ($m[1]) {
                        case '>':
                            $method = 'andGreaterThan';
                            break;
                        case '<':
                            $method = 'andLessThan';
                            break;
                        case '>=':
                            $method = 'andGreaterEqual';
                            break;
                        case '<=':
                            $method = 'andLessEqual';
                            break;
                        case '<>':
                        case '!':
                            $method = 'andNotEqual';
                            break;
                        default:
                            $method = 'andEqual';
                            break;
                    }

                    if ($type === 'date') {
                        $v = $m[2];

                        if (!Validator::date($v, 'Y-m-d') && !Validator::date($v, 'Y-m-d H:i:s')) {
                            throw new BadRequest('Data inválida: ' . $v);
                        }

                        $f = 'YYYY-MM-DD HH24:MI:SS';

                        if ($format === 'Y-m-d H:i:s' && Validator::date($v, 'Y-m-d')) {
                            $field = "TRUNC($field)";
                        }

                        $this->$method($field, "TO_DATE('$v', '$f')", self::RAW_QUERY);
                        break;
                    }

                    $this->$method($field, $m[2]);
                    break;
                } elseif ($value === "\x00") {
                    $this->andIsNull($field);
                } elseif ($value === "!\x00") {
                    $this->andIsNotNull($field);
                } else {
                    throw new BadRequest("Valor inválido: $value!");
                }

                break;
            case 'string':
                $parts = explode(' ', urldecode($value));
                foreach ($parts as $p) {
                    $this->andLike("UPPER($field)", "UPPER('%$p%')", self::RAW_QUERY);
                }
                break;
            default:
                if (preg_match('/^\[.*\]$/', $value, $m)) {
                    $this->andIn($field, explode(',', substr($m[0], 1, -1)));
                } else {

                    $m = 'andEqual';
                    $s = urldecode($value);
                    $a = '';
                    $b = '';

                    if (substr($s, 0, 1) === '%') {
                        $a = '%';
                        $m = 'andLike';
                    }

                    if (substr($s, -1) === '%') {
                        $b = '%';
                        $m = 'andLike';
                    }

                    $f = substr($s, 0, 1);
                    $l = substr($s, -1);

                    if ($f === '"' && $l === '"') {
                        $s = substr($s, 1, -1);
                    }

                    if ($f === '!') {
                        $m = 'andNotLike';
                        $s = substr($s, 1);
                        $f = substr($s, 0, 1);
                    }

                    if ($f === '%') {
                        $f = substr($s, 0, 1);
                    } else {
                        $a = '';
                    }

                    if ($l === '%') {
                        $s = substr($s, 0, -1);
                    } else {
                        $b = '';
                    }

                    $this->$m($field, "{$a}{$s}{$b}");
                }
        }

        return $this;
    }
    private function checkIfFieldExists($field, $modifiers = 0)
    {
        $field = trim(urldecode($field));

        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        if (($modifiers & self::RAW_QUERY) === self::RAW_QUERY) {
            return $field = [
                'name' => $field,
                'type' => ''
            ];
        }

        if (isset($this->modelFields[$field])) {
            return $this->modelFields[$field];
        }

        if (($modifiers & self::RAW_QUERY) !== self::RAW_QUERY) {
            throw new BadRequest('O campo {' . $field . '} não existe no modelo ' . get_class($this->getModel()) . '!');
        }
    }
    public function orderBy($field, $direction = 'ASC', $modifiers = 0)
    {
        $this->checkIfFieldExists($field, $modifiers);

        $this->orderBy[$field] = strtoupper($direction);
        return $this;
    }
    public function orBitAnd($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('OR', '&', $field, $value, $modifiers);
        return $this;
    }
    public function orIsNull($field, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);
        $sql = "{$field['name']} IS null OR ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;
        
        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function orEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('OR', '=', $field, $value, $modifiers);
        return $this;
    }
    public function orNotEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('OR', '<>', $field, $value, $modifiers);
        return $this;
    }
    public function orLessEqual($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('OR', '<=', $field, $value, $modifiers);
        return $this;
    }
    public function orLessThan($field, $value, $modifiers = 0)
    {
        $this->addCriteriaParser('OR', '<', $field, $value, $modifiers);
        return $this;
    }
    public function orIsNotNull($field, $modifiers = 0)
    {
        $field = $this->checkIfFieldExists($field);
        $sql = "{$field['name']} IS NOT null OR ";

        $this->criteria .= $sql;

        $fixed = ($modifiers & self::FIXED_QUERY) === self::FIXED_QUERY;

        if ($fixed) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    private function escape($value) 
    {
        return str_replace(['\\', "\0", "\n", "\r", "'", "\x1a"], ['\\\\', '\\0', '\\n', '\\r', "''", '\\Z'], $value);
    }
    public function getModelFields() 
    {
        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        return $this->modelFields;
    }
    private function getLimitSql()
    {
        $sql = '';

        if ($this->perPage > 0 && $this->dbms == 1) {
            $sql .= ' LIMIT ' . (($this->page - 1) * $this->perPage) . ',' . $this->perPage;
        }

        return $sql;
    }
    public function getOrderBySql()
    {
        $sql = '';

        if (!empty($this->orderBy)) {
            $sql = ' ORDER BY ';

            foreach ($this->orderBy as $k => $v) {
                $sql .= $k . ' ' . ($v === '' ? '' : strtoupper($v)) . ', ';
            }
        }

        $sql = substr($sql, 0, -2);
        return $sql;
    }
    public function getPage()
    {
        return $this->page;
    }
    public function getPerPage()
    {
        return $this->perPage;
    }
    protected function getSelectFields($format = true, $aliases = false)
    {
        $list     = '';
        $selected = $this->selectedFields;

        if (empty($selected)) {
            $selected = $this->getModelFields();
        }

        foreach ($selected as $k => $v) {
            $alias = $this->map[$k] ?? null;
            $field = $v['name'];

            if ($format && $v['type'] === 'date' && $this->dbms === 2) {
                $mask  = 'YYYY-MM-DD' . ($v['format'] === 'Y-m-d H:i:s' ? ' HH24:MI:SS' : '');
                $field = "TO_CHAR($field, '$mask') AS " . ($alias ?? $field);
            } else {
                if ($aliases && !is_null($alias)) {
                    $field = $alias;
                } else {
                    $field .= is_null($alias) ? '' : " AS $alias";
                }
            }

            $list .= "$field, ";
        }

        $list = substr($list, 0, -2);

        return $list;
    }
    public function getWhereSql($fixed = false)
    {
        $criteria = $fixed ? $this->fixedCriteria : $this->criteria;
        $sql = '';

        if (!is_null($criteria)) {
            $sql = ' WHERE '. $criteria;

            if (preg_match('/\s(AND|OR)\s\)?$/', $sql, $m)) {
                $matched = $m[0];
                $length = strlen($matched);
                $sql = substr($sql, 0, -$length);

                if (substr($matched, -1, 1) == ')') {
                    $sql .= ')';
                }
            }
        }

        return $sql;
    }
    public function renderSelectSql()
    {
        $sql = $this->sql;

        if (is_null($sql)) {
            if ($this->selectSqlReady) {
                return $this->getSelectSql();
            }

            $sql = 'SELECT ' . $this->getSelectFields() . ' FROM ' . $this->model->getTableName() . $this->getWhereSql() . $this->getOrderBySql() . $this->getLimitSql();
        }

        // \MonitoLib\Dev::e($sql);

        $page    = $this->getPage();
        $perPage = $this->getPerPage();

        if ($this->dbms === self::DB_ORACLE && $perPage > 0) {
            $startRow = (($page - 1) * $perPage) + 1;
            $endRow   = $perPage * $page;
            $sql      = "SELECT {$this->getSelectFields(false, true)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sql) a) WHERE rown_ BETWEEN $startRow AND $endRow";
        }

        $this->reset();

        return $sql;
    }
    public function reset()
    {
        $this->criteria      = null;
        $this->countCriteria = null;
        $this->fixedCriteria = null;
        $this->page          = 1;
        $this->perPage       = 0;
        $this->sql           = null;
        $this->reseted       = true;
        return $this;
    }
    public function renderCountSql($all = false)
    {
        return 'SELECT COUNT(*) FROM ' . $this->model->getTableName() . $this->getWhereSql($all);
    }
    public function renderDeleteSql()
    {
        return 'DELETE FROM ' . $this->model->getTableName() . $this->getWhereSql();
    }
    public function setDbms($dbms)
    {
        $this->dbms = $dbms;
    }
    public function setMap($map, $convertName = true)
    {
        $this->map = $map;
        $this->convertName = $convertName;
        return $this;
    }
    public function setFields(array $fields) : object
    {
        if (is_null($fields)) {
            return $this;
        }

        if (empty($fields)) {
            throw new BadRequest('É preciso informar pelo menos um campo!');
        }

        foreach ($fields as $f) {
            $field = $this->checkIfFieldExists($f);

            if (is_array($field)) {
                $this->selectedFields[$f] = $field;
            } else {
                $errors = $field;
            }
        }

        if (!empty($errors)) {
            throw new BadRequest('Campo informado não existe no modelo de dados!', $errors);
        }

        $this->fields = $fields;
        return $this;
    }
    public function setModel($model) {
        $this->model = $model;
        return $this;
    }
    public function setOrderBy($orderBy)
    {
        if (!empty($orderBy)) {
            foreach ($orderBy as $f => $d) {
                if (is_numeric($f)) {
                    $f = $d;
                }

                $this->orderBy($f, $d);
            }
        }

        return $this;
    }
    public function setPage($page)
    {
        if (!is_numeric($page) && !is_integer(+$page)) {
            throw new BadRequest('Número da página inválido!');
        }

        $this->page = $page;

        return $this;
    }
    public function setPerPage($perPage)
    {
        if (!is_numeric($perPage) && !is_integer(+$perPage)) {
            throw new BadRequest('Quantidade por página inválida!');
        }

        $this->perPage = $perPage;
        return $this;
    }
    public function setQuery($query)
    {
        if (!empty($query)) {
            foreach ($query as $field) {
                $key = key($field);
                $this->andFilter($key, $field[$key]);
            }
        }

        return $this;
    }
    public function setSql($sql)
    {
        $this->sql = $sql;
        return $this;
    }
    public function setSqlCount($sqlCount)
    {
        $this->sqlCount = $sqlCount;
        return $this;
    }
    public function setTableName($tableName) {
        $this->tableName = $tableName;
        return $this;
    }
}

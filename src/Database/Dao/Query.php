<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class Query
{
    const VERSION = '2.0.2';
    /**
    * 2.0.2 - 2021-05-04
    * dev update
    *
    * 2.0.1 - 2020-09-19
    * fix: equal, noEqual value type
    *
    * 2.0.0 - 2020-09-19
    * new: renamed filter methods
    * new: refactored class
    *
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

    // Options flags
    const FIXED_QUERY = 1;
    const CHECK_NULL  = 2;
    const RAW_QUERY   = 4;
    // const ALL         = 8;
    // const ANY         = 8;
    const OR          = 8;
    const START_GROUP = 16;
    const END_GROUP   = 32;

    // Database types
    const DB_MYSQL  = 1;
    const DB_ORACLE = 2;
    const DB_MONGO  = 4;

    protected $convertName = true;
    private $countSql;
    private $countSqlReady = false;
    private $criteria;
    private $fixedCriteria;
    private $groups = 0;
    private $map = [];
    private $modelFields;
    private $orderBy = [];
    private $orderBySql;
    private $orderBySqlReady = false;
    private $page    = 1;
    private $perPage = 0;
    private $reseted = false;
    private $selectedFields;
    private $selectSql;
    private $selectSqlReady = false;
    private $sql;
    private $sqlCount;
    private $queryParser;

    // public function __construct()
    // {
    //     \MonitoLib\Dev::pre($this);
    // }

    // public function all(string $field, int $value, int $options = 0) : self
    // {
    //     $this->parseCriteria('ALL', $field, $value, null, $options);
    //     return $this;
    // }
    // public function any(string $field, int $value, int $options = 0) : self
    // {
    //     $this->parseCriteria('ANY', $field, $value, null, $options);
    //     return $this;
    // }
    public function between(string $field, $value1, $value2, int $options = 0) : self
    {
        $this->parseCriteria('BETWEEN', $field, $value1, $value2, $options);
        return $this;
    }
    public function bit(string $field, int $value, int $options = 0) : self
    {
        $this->parseCriteria('&', $field, $value, null, $options);
        return $this;
    }
    private function checkField(string $field, bool $rawQuery = false) : ?array
    {
        if ($rawQuery) {
            return $field = [
                'name'   => $field,
                'type'   => 'string',
                'format' => null
            ];
        }

        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        if (!isset($this->modelFields[$field])) {
            throw new BadRequest('O campo {' . $field . '} não existe no modelo ' . get_class($this->getModel()) . '!');
        }

        return $this->modelFields[$field];
    }
    public function equal(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('=', $field, $value, null, $options);
        return $this;
    }
    private function escape(string $value) : string
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "''", '\\Z'],
            $value
        );
    }
    public function exists(string $value, int $options = 0) : self
    {
        $this->parseCriteria('EXISTS', null, $value, null, $options);
        return $this;
    }
    public function getModelFields() : ?array
    {
        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        return $this->modelFields;
    }
    public function getPage() : int
    {
        return $this->page;
    }
    public function getPerPage() : int
    {
        return $this->perPage;
    }
    public function greater(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('>', $field, $value, null, $options);
        return $this;
    }
    public function greaterEqual(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('>=', $field, $value, null, $options);
        return $this;
    }
    public function in(string $field, array $values, int $options = 0) : self
    {
        $this->parseCriteria('IN', $field, $values, null, $options);
        return $this;
    }
    public function isNotNull(string $field, int $options = 0) : self
    {
        $this->parseCriteria('IS NOT NULL', $field, null, null, $options);
        return $this;
    }
    public function isNull(string $field, int $options = 0) : self
    {
        $this->parseCriteria('IS NULL', $field, null, null, $options);
        return $this;
    }
    public function less(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('<', $field, $value, null, $options);
        return $this;
    }
    public function lessEqual(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('<=', $field, $value, null, $options);
        return $this;
    }
    public function like(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('LIKE', $field, $value, null, $options);
        return $this;
    }
    public function notEqual(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('<>', $field, $value, null, $options);
        return $this;
    }
    public function notExists(string $value, int $options = 0) : self
    {
        $this->parseCriteria('NOT EXISTS', null, $value, null, $options);
        return $this;
    }
    public function notIn(string $field, array $values, int $options = 0) : self
    {
        $this->parseCriteria('NOT IN', $field, $values, null, $options);
        return $this;
    }
    public function notLike(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('NOT LIKE', $field, $value, null, $options);
        return $this;
    }
    public function orderBy($field, $direction = 'ASC', $modifiers = 0) : self
    {
        $this->checkField($field, $modifiers);

        $this->orderBy[$field] = strtoupper($direction);
        return $this;
    }
    private function parseCriteria(string $comparisonOperator, ?string $field, $value1, $value2 = null, int $options = 0) : self
    {
        $options    = $this->parseOptions($options);
        $fixedQuery = $options->fixedQuery;
        $checkNull  = $options->checkNull;
        $rawQuery   = $options->rawQuery;
        $operator   = $options->operator;
        $startGroup = $options->startGroup;
        $endGroup   = $options->endGroup;

        $type   = null;
        $name   = null;
        $format = null;

        if (is_null($field)) {
            $rawQuery = true;
        } else {
            $field  = $this->checkField($field, $rawQuery);
            $type   = $field['type'];
            $name   = $field['name'];
            $format = $field['format'];
        }

        if (in_array($comparisonOperator, ['LIKE', 'NOT LIKE'])) {
            $type = 'string';
        }

        if (is_null($value1) && $checkNull) {
            return $this->isNull($field, $fixedQuery);
        }

        if ($this->dbms === self::DB_ORACLE && $type === 'date') {
            if (!Validator::date($value1, 'Y-m-d') && !Validator::date($value1, 'Y-m-d H:i:s')) {
                throw new BadRequest('Data inválida: ' . $value1);
            }

            $f = 'YYYY-MM-DD HH24:MI:SS';

            if ($format === 'Y-m-d H:i:s' && Validator::date($value1, 'Y-m-d')) {
                $name = "TRUNC($field)";
            }

            $value1 = "TO_DATE('$value', '$f')";
            $rawQuery = true;
        }

        if ($rawQuery || in_array($type, ['int','float'])) {
            $q = '';
        } else {
            $q = '\'';
        }

        $sql = '';

        if (!is_null($name)) {
            $sql .= "$name ";
        }

        $sql .= $comparisonOperator;

        if (!is_null($value1)) {
            $sql .= ' ';

            if (in_array($comparisonOperator, ['EXISTS', 'NOT EXISTS'])) {
                $sql .= '(';
            }

            if (is_array($value1)) {
                $sql .= '(';

                foreach ($value1 as $v) {
                    $sql .= $q . ($rawQuery ? $v : $this->escape($v)) . "$q,";
                }

                $sql = substr($sql, 0, -1) . ')';
            } else {
                $sql .= $q . ($rawQuery ? $value1 : $this->escape($value1)) . $q;

                if (in_array($comparisonOperator, ['EXISTS', 'NOT EXISTS'])) {
                    $sql .= ')';
                }
            }
        }

        // Between
        if (!is_null($value2)) {
            $sql .= " AND $q"
                . ($rawQuery ? $value2 : $this->escape($value2))
                . "$q";
        }

        if ($startGroup) {
            $sql = '(' . $sql;
        }

        if ($endGroup) {
            $sql .= ')';
        }

        $sql = " $operator $sql";

        $this->criteria .= $sql;

        if ($fixedQuery) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }
    public function parseFilter($field, $value, $type = 'string') : self
    {
        $f = $this->checkField($field);
        $type = $f['type'];
        $format = $f['format'];

        switch ($type) {
            case 'date':
            case 'double':
            case 'float':
            case 'int':
                $value = urldecode($value);

                // Verifica se é intervalo
                // if (preg_match('/^([0-9.]+)-([0-9.]+)$/', $value, $m)) {
                //     $this->criteria .= "$field BETWEEN $m[1] AND $m[2] AND ";
                //     break;
                // }

                // Verifica se tem algum modificador
                if (preg_match('/^([><=!]{1,2})?([0-9.\-\s:]+)$/', $value, $m)) {
                    switch ($m[1]) {
                        case '>':
                            $method = 'greater';
                            break;
                        case '<':
                            $method = 'less';
                            break;
                        case '>=':
                            $method = 'greaterEqual';
                            break;
                        case '<=':
                            $method = 'lessEqual';
                            break;
                        case '<>':
                        case '!':
                            $method = 'notEqual';
                            break;
                        default:
                            $method = 'equal';
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
                    $this->isNull($field);
                } elseif ($value === "!\x00") {
                    $this->isNotNull($field);
                } else {
                    throw new BadRequest("Valor inválido: $value!");
                }

                break;
            case 'string':
                $parts = explode(' ', urldecode($value));
                foreach ($parts as $p) {
                    $this->like("UPPER($field)", "UPPER('%$p%')", self::RAW_QUERY);
                }
                break;
            default:
                if (preg_match('/^\[.*\]$/', $value, $m)) {
                    $this->in($field, explode(',', substr($m[0], 1, -1)));
                } else {

                    $m = 'equal';
                    $s = urldecode($value);
                    $a = '';
                    $b = '';

                    if (substr($s, 0, 1) === '%') {
                        $a = '%';
                        $m = 'like';
                    }

                    if (substr($s, -1) === '%') {
                        $b = '%';
                        $m = 'like';
                    }

                    $f = substr($s, 0, 1);
                    $l = substr($s, -1);

                    if ($f === '"' && $l === '"') {
                        $s = substr($s, 1, -1);
                    }

                    if ($f === '!') {
                        $m = 'notLike';
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
    private function parseOptions(int $options) : \stdClass
    {
        $return = new \stdClass();
        $return->fixedQuery = false;
        $return->checkNull  = false;
        $return->rawQuery   = false;
        $return->operator   = 'AND';
        $return->startGroup = false;
        $return->endGroup   = false;

        if ($options > 0) {
            $return->fixedQuery = ($options & self::FIXED_QUERY) === self::FIXED_QUERY;
            $return->checkNull  = ($options & self::CHECK_NULL) === self::CHECK_NULL;
            $return->rawQuery   = ($options & self::RAW_QUERY) === self::RAW_QUERY;
            $return->operator   = ($options & self::OR) === self::OR ? 'OR' : 'AND';
            $return->startGroup = ($options & self::START_GROUP) === self::START_GROUP;
            $return->endGroup   = ($options & self::END_GROUP) === self::END_GROUP;
        }

        if ($return->startGroup) {
            $this->groups++;
        }

        if ($return->endGroup) {
            $this->groups--;
        }

        return $return;
    }
    public function renderCountSql(bool $all = false) : string
    {
        return 'SELECT COUNT(*) FROM ' . $this->model->getTableName() . $this->renderWhereSql($all);
    }
    public function renderDeleteSql() : string
    {
        return 'DELETE FROM ' . $this->model->getTableName() . $this->renderWhereSql();
    }
    protected function renderFieldsSql(bool $format = true, bool $aliases = false) : string
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
    private function renderLimitSql() : string
    {
        $sql = '';

        if ($this->perPage > 0 && $this->dbms == 1) {
            $sql .= ' LIMIT ' . (($this->page - 1) * $this->perPage) . ',' . $this->perPage;
        }

        return $sql;
    }
    public function renderOrderBySql() : string
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
    public function renderSelectSql() : string
    {
        $sql = $this->sql;

        if (is_null($sql)) {
            if ($this->selectSqlReady) {
                return $this->getSelectSql();
            }

            $sql = 'SELECT ' . $this->renderFieldsSql() . ' FROM ' . $this->model->getTableName() . $this->renderWhereSql() . $this->renderOrderBySql() . $this->renderLimitSql();
        }

        $page    = $this->getPage();
        $perPage = $this->getPerPage();

        if ($this->dbms === self::DB_ORACLE && $perPage > 0) {
            $startRow = (($page - 1) * $perPage) + 1;
            $endRow   = $perPage * $page;
            $sql      = "SELECT {$this->renderFieldsSql(false, true)} FROM (SELECT a.*, ROWNUM as rown_ FROM ($sql) a) WHERE rown_ BETWEEN $startRow AND $endRow";
        }

        $this->reset();

        return $sql;
    }
    public function renderWhereSql(bool $fixed = false) : string
    {
        if ($this->groups !== 0) {
            throw new InternalError('Agrupamento inválido na cláusula WHERE');
        }

        $criteria = $fixed ? $this->fixedCriteria : $this->criteria;
        $sql = '';

        if (!is_null($criteria)) {
            $sql = ' WHERE '. substr($criteria, strpos($criteria, ' ', 2) + 1);
        }

        return $sql;
    }
    public function reset() : self
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
    public function setDbms(int $dbms) : self
    {
        $this->dbms = $dbms;
        return $this;
    }
    public function setFields(array $fields = null) : self
    {
        if (is_null($fields)) {
            return $this;
        }

        if (empty($fields)) {
            throw new BadRequest('É preciso informar pelo menos um campo!');
        }

        foreach ($fields as $f) {
            $field = $this->checkField($f);

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
    public function setMap(array $map, bool $convertName = true) : self
    {
        $this->map = $map;
        $this->convertName = $convertName;
        return $this;
    }
    public function setModel(string $model) : self
    {
        $this->model = $model;
        return $this;
    }
    public function setOrderBy(?array $orderBy) : self
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
    public function setPage(int $page) : self
    {
        if (!is_integer(+$page)) {
            throw new BadRequest('Número da página inválido!');
        }

        $this->page = $page;
        return $this;
    }
    public function setPerPage(int $perPage) : self
    {
        if (!is_integer(+$perPage)) {
            throw new BadRequest('Quantidade por página inválida!');
        }

        $this->perPage = $perPage;
        return $this;
    }
    public function setQuery(?array $query) : self
    {
        if (!empty($query)) {
            foreach ($query as $field) {
                $key = key($field);
                $this->parseFilter($key, $field[$key]);
            }
        }

        return $this;
    }
    public function setSql(string $sql) : self
    {
        $this->sql = $sql;
        return $this;
    }
    public function setSqlCount(string $sqlCount) : self
    {
        $this->sqlCount = $sqlCount;
        return $this;
    }
    public function setTableName(string $tableName) : self
    {
        $this->tableName = $tableName;
        return $this;
    }
}
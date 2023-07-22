<?php
/**
 * Database\Query\Sql.
 *
 * @version 2.1.0
 */

namespace MonitoLib\Database\Query;

use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\InternalErrorException;
use MonitoLib\Validator;
use stdClass;

class Sql extends \MonitoLib\Database\Dao\Base
{
    // Options flags
    public const FIXED_QUERY = 1;
    public const CHECK_NULL = 2;
    public const RAW_QUERY = 4;
    public const OR = 8;
    public const START_GROUP = 16;
    public const END_GROUP = 32;

    // Database types
    public const DB_MYSQL = 1;
    public const DB_ORACLE = 2;

    protected $convertName = true;
    private $countCriteria;
    private $countSql;
    private $countSqlReady = false;
    private $criteria;
    private $fields;
    private $fixedCriteria;
    private $groups = 0;
    private $map = [];
    private $modelFields;
    private $orderBy = [];
    private $orderBySql;
    private $orderBySqlReady = false;
    private $page = 1;
    private $perPage = 0;
    private $reseted = false;
    private $selectedFields;
    private $selectSql;
    private $selectSqlReady = false;
    private $sql;
    private $sqlCount;

    public function __construct()
    {
        parent::__construct();
    }

    public function between(string $field, $value1, $value2, int $options = 0): self
    {
        $this->parseCriteria('BETWEEN', $field, $value1, $value2, $options);

        return $this;
    }

    public function bit(string $field, int $value, int $options = 0): self
    {
        $this->parseCriteria('&', $field, $value, null, $options);

        return $this;
    }

    public function count()
    {
        return 0;
    }

    public function equal(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('=', $field, $value, null, $options);

        return $this;
    }

    public function exists(string $value, int $options = 0): self
    {
        $this->parseCriteria('EXISTS', null, $value, null, $options);

        return $this;
    }

    public function first()
    {
        return (object) [];
    }

    public function get()
    {
        return $this->first();
    }

    public function getModelFields(): ?array
    {
        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        return $this->modelFields;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function greater(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('>', $field, $value, null, $options);

        return $this;
    }

    public function greaterEqual(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('>=', $field, $value, null, $options);

        return $this;
    }

    public function in(string $field, array $values, int $options = 0): self
    {
        $this->parseCriteria('IN', $field, $values, null, $options);

        return $this;
    }

    public function isNotNull(string $field, int $options = 0): self
    {
        $this->parseCriteria('IS NOT NULL', $field, null, null, $options);

        return $this;
    }

    public function isNull(string $field, int $options = 0): self
    {
        $this->parseCriteria('IS NULL', $field, null, null, $options);

        return $this;
    }

    public function less(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('<', $field, $value, null, $options);

        return $this;
    }

    public function lessEqual(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('<=', $field, $value, null, $options);

        return $this;
    }

    public function like(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('LIKE', $field, $value, null, $options);

        return $this;
    }

    public function list()
    {
        return [];
    }

    public function notEqual(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('<>', $field, $value, null, $options);

        return $this;
    }

    public function notExists(string $value, int $options = 0): self
    {
        $this->parseCriteria('NOT EXISTS', null, $value, null, $options);

        return $this;
    }

    public function notIn(string $field, array $values, int $options = 0): self
    {
        $this->parseCriteria('NOT IN', $field, $values, null, $options);

        return $this;
    }

    public function notLike(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('NOT LIKE', $field, $value, null, $options);

        return $this;
    }

    public function orderBy($field, $direction = 'ASC', $modifiers = 0): self
    {
        $this->checkField($field, $modifiers);

        $this->orderBy[$field] = strtoupper($direction);

        return $this;
    }

    public function parseFilter($field, $value, $type = 'string'): self
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

                    if ('date' === $type) {
                        $v = $m[2];

                        if (!Validator::date($v, 'Y-m-d') && !Validator::date($v, 'Y-m-d H:i:s')) {
                            throw new BadRequestException('Invalid date: ' . $v);
                        }

                        $f = 'YYYY-MM-DD HH24:MI:SS';

                        if ('Y-m-d H:i:s' === $format && Validator::date($v, 'Y-m-d')) {
                            $field = "TRUNC({$field})";
                        }

                        $this->{$method}($field, "TO_DATE('{$v}', '{$f}')", self::RAW_QUERY);

                        break;
                    }

                    $this->{$method}($field, $m[2]);

                    break;
                }

                if ("\x00" === $value) {
                    $this->isNull($field);
                } elseif ("!\x00" === $value) {
                    $this->isNotNull($field);
                } else {
                    throw new BadRequestException("Invalid value: {$value}!");
                }

                break;

            case 'string':
                $parts = explode(' ', urldecode($value));

                foreach ($parts as $p) {
                    $this->like("UPPER({$field})", "UPPER('%{$p}%')", self::RAW_QUERY);
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

                    if ('%' === substr($s, 0, 1)) {
                        $a = '%';
                        $m = 'like';
                    }

                    if ('%' === substr($s, -1)) {
                        $b = '%';
                        $m = 'like';
                    }

                    $f = substr($s, 0, 1);
                    $l = substr($s, -1);

                    if ('"' === $f && '"' === $l) {
                        $s = substr($s, 1, -1);
                    }

                    if ('!' === $f) {
                        $m = 'notLike';
                        $s = substr($s, 1);
                        $f = substr($s, 0, 1);
                    }

                    if ('%' === $f) {
                        $f = substr($s, 0, 1);
                    } else {
                        $a = '';
                    }

                    if ('%' === $l) {
                        $s = substr($s, 0, -1);
                    } else {
                        $b = '';
                    }

                    $this->{$m}($field, "{$a}{$s}{$b}");
                }
        }

        return $this;
    }

    public function renderCountSql(bool $all = false): string
    {
        return 'SELECT COUNT(*) FROM ' . $this->model->getTableName() . $this->renderWhereSql($all);
    }

    public function renderDeleteSql(): string
    {
        return 'DELETE FROM ' . $this->model->getTableName() . $this->renderWhereSql();
    }

    public function renderOrderBySql(): string
    {
        $sql = '';

        if (!empty($this->orderBy)) {
            $sql = ' ORDER BY ';

            foreach ($this->orderBy as $k => $v) {
                $sql .= $k . ' ' . ('' === $v ? '' : strtoupper($v)) . ', ';
            }
        }

        return substr($sql, 0, -2);
    }

    public function renderSelectSql(): string
    {
        $sql = $this->sql;

        if (is_null($sql)) {
            $sql = 'SELECT ' . $this->renderFieldsSql() . ' FROM ' . $this->model->getTableName()
                . $this->renderWhereSql() . $this->renderOrderBySql() . $this->renderLimitSql();
        }

        $this->reset();

        return $sql;
    }

    public function renderWhereSql(bool $fixed = false): string
    {
        if (0 !== $this->groups) {
            throw new InternalErrorException('Invalid group');
        }

        $criteria = $fixed ? $this->fixedCriteria : $this->criteria;
        $sql = '';

        if (!is_null($criteria)) {
            $sql = ' WHERE ' . substr($criteria, strpos($criteria, ' ', 2) + 1);
        }

        return $sql;
    }

    public function reset(): self
    {
        $this->criteria = null;
        $this->countCriteria = null;
        $this->fixedCriteria = null;
        $this->page = 1;
        $this->perPage = 0;
        $this->sql = null;
        $this->reseted = true;

        return $this;
    }

    public function setDbms(int $dbms): self
    {
        $this->dbms = $dbms;

        return $this;
    }

    public function setFields(?array $fields = null): self
    {
        if (is_null($fields)) {
            return $this;
        }

        if (empty($fields)) {
            throw new BadRequestException('Empty fields list');
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
            throw new BadRequestException('Unknown field', $errors);
        }

        $this->fields = $fields;

        return $this;
    }

    public function setMap(array $map, bool $convertName = true): self
    {
        $this->map = $map;
        $this->convertName = $convertName;

        return $this;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function setOrderBy(?array $orderBy): self
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

    public function setPage(int $page): self
    {
        if (!is_integer(+$page)) {
            throw new BadRequestException('Invalid page number');
        }

        $this->page = $page;

        return $this;
    }

    public function setPerPage(int $perPage): self
    {
        if (!is_integer(+$perPage)) {
            throw new BadRequestException('Invalid page limit');
        }

        $this->perPage = $perPage;

        return $this;
    }

    public function setQuery(?array $query): self
    {
        if (!empty($query)) {
            foreach ($query as $field) {
                $key = key($field);
                $this->parseFilter($key, $field[$key]);
            }
        }

        return $this;
    }

    public function setSql(string $sql): self
    {
        $this->sql = $sql;

        return $this;
    }

    public function setSqlCount(string $sqlCount): self
    {
        $this->sqlCount = $sqlCount;

        return $this;
    }

    public function setTableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    protected function renderFieldsSql(bool $format = true, bool $aliases = false): string
    {
        $list = '';
        $selected = $this->selectedFields;

        if (empty($selected)) {
            $selected = $this->getModelFields();
        }

        foreach ($selected as $k => $v) {
            $alias = $this->map[$k] ?? null;
            $field = $v['name'];

            if ($format && 'date' === $v['type'] && 2 === $this->dbms) {
                $mask = 'YYYY-MM-DD' . ('Y-m-d H:i:s' === $v['format'] ? ' HH24:MI:SS' : '');
                $field = "TO_CHAR({$field}, '{$mask}') AS " . ($alias ?? $field);
            } else {
                if ($aliases && !is_null($alias)) {
                    $field = $alias;
                } else {
                    $field .= is_null($alias) ? '' : " AS {$alias}";
                }
            }

            $list .= "{$field}, ";
        }

        return substr($list, 0, -2);
    }

    private function checkField(string $field, bool $rawQuery = false): ?array
    {
        if ($rawQuery) {
            return $field = [
                'name' => $field,
                'type' => 'string',
                'format' => null,
            ];
        }

        if (is_null($this->modelFields)) {
            $this->modelFields = $this->getModel()->getFields();
        }

        if (!isset($this->modelFields[$field])) {
            throw new BadRequestException("Unknow field {$field} in " . get_class($this->getModel()));
        }

        return $this->modelFields[$field];
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', "\0", "\n", "\r", "'", "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "''", '\\Z'],
            $value
        );
    }

    private function parseCriteria(string $comparisonOperator, ?string $field, $value1, $value2 = null, int $options = 0): self
    {
        $options = $this->parseOptions($options);
        $fixedQuery = $options->fixedQuery;
        $checkNull = $options->checkNull;
        $rawQuery = $options->rawQuery;
        $operator = $options->operator;
        $startGroup = $options->startGroup;
        $endGroup = $options->endGroup;

        $type = null;
        $name = null;
        $format = null;

        if (is_null($field)) {
            $rawQuery = true;
        } else {
            $field = $this->checkField($field, $rawQuery);
            $type = $field['type'];
            $name = $field['name'];
            $format = $field['format'];
        }

        if (in_array($comparisonOperator, ['LIKE', 'NOT LIKE'])) {
            $type = 'string';
        }

        if (is_null($value1) && $checkNull) {
            return $this->isNull($field, $fixedQuery);
        }

        if (self::DB_ORACLE === $this->dbms && 'date' === $type) {
            if (!Validator::date($value1, 'Y-m-d') && !Validator::date($value1, 'Y-m-d H:i:s')) {
                throw new BadRequestException('Invalid date: ' . $value1);
            }

            $f = 'YYYY-MM-DD HH24:MI:SS';

            if ('Y-m-d H:i:s' === $format && Validator::date($value1, 'Y-m-d')) {
                $name = "TRUNC({$field})";
            }

            $value1 = "TO_DATE('{$value1}', '{$f}')";
            $rawQuery = true;
        }

        if ($rawQuery || in_array($type, ['int', 'float'])) {
            $q = '';
        } else {
            $q = '\'';
        }

        $sql = '';

        if (!is_null($name)) {
            $sql .= "{$name} ";
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
                    $sql .= $q . ($rawQuery ? $v : $this->escape($v)) . "{$q},";
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
            $sql .= " AND {$q}"
                . ($rawQuery ? $value2 : $this->escape($value2))
                . "{$q}";
        }

        if ($startGroup) {
            $sql = '(' . $sql;
        }

        if ($endGroup) {
            $sql .= ')';
        }

        $sql = " {$operator} {$sql}";

        $this->criteria .= $sql;

        if ($fixedQuery) {
            $this->fixedCriteria .= $sql;
        }

        return $this;
    }

    private function parseOptions(int $options): stdClass
    {
        $return = new stdClass();
        $return->fixedQuery = false;
        $return->checkNull = false;
        $return->rawQuery = false;
        $return->operator = 'AND';
        $return->startGroup = false;
        $return->endGroup = false;

        if ($options > 0) {
            $return->fixedQuery = ($options & self::FIXED_QUERY) === self::FIXED_QUERY;
            $return->checkNull = ($options & self::CHECK_NULL) === self::CHECK_NULL;
            $return->rawQuery = ($options & self::RAW_QUERY) === self::RAW_QUERY;
            $return->operator = ($options & self::OR) === self::OR ? 'OR' : 'AND';
            $return->startGroup = ($options & self::START_GROUP) === self::START_GROUP;
            $return->endGroup = ($options & self::END_GROUP) === self::END_GROUP;
        }

        if ($return->startGroup) {
            ++$this->groups;
        }

        if ($return->endGroup) {
            --$this->groups;
        }

        return $return;
    }

    private function renderLimitSql(): string
    {
        $sql = '';

        if ($this->perPage > 0 && self::DB_MYSQL === $this->dbms) {
            $sql .= ' LIMIT ' . (($this->page - 1) * $this->perPage) . ',' . $this->perPage;
        }

        if ($this->perPage > 0 && self::DB_ORACLE === $this->dbms) {
            if ($this->page > 1) {
                $offset = (($this->page - 1) * $this->perPage);
                $sql .= " OFFSET {$offset} ROW";
            }

            $sql .= " FETCH NEXT {$this->perPage} ROWS ONLY";
        }

        return $sql;
    }
}

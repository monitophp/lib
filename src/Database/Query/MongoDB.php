<?php
namespace MonitoLib\Database\Query;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Validator;

class MongoDB extends \MonitoLib\Database\Dao\Base
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-03-04
    * Initial release
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

    protected $fields = [];

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
    private $filter = [];

    // public function all(string $field, int $value, int $options = 0) : self
    // {
    //     $this->parseCriteria('ALL', $field, $value, $options);
    //     return $this;
    // }
    // public function any(string $field, int $value, int $options = 0) : self
    // {
    //     $this->parseCriteria('ANY', $field, $value, $options);
    //     return $this;
    // }
    public function between(string $field, $value1, $value2, int $options = 0) : self
    {
        $this->parseCriteria('>=', $field, $value1, $options);
        $this->parseCriteria('<=', $field, $value2, $options);
        return $this;
    }
    public function bit(string $field, int $value, int $options = 0) : self
    {
        $this->parseCriteria('&', $field, $value, $options);
        return $this;
    }
    private function checkField(string $field, bool $rawQuery = false) : \MonitoLib\Database\Model\Field
    {
        if ($rawQuery) {
            // $field = new \MonitoLib\Database\Model\Field();
            // $field->setName($field);

            return $field = [
                'name'   => $field,
                'type'   => 'string',
                'format' => null
            ];
        }

        $fields = explode('.', $field);
        $model  = $this->model;

        foreach ($fields as $f) {
            $field = $model->getField($f);
            $type  = $field->getType();

            if (class_exists($type)) {
                $model = str_replace('\\Dto\\', '\\Model\\', $type);
                $model = new $model();
            } else {
                break;
            }
        }

        // if (!isset($this->modelFields[$field])) {
        //     throw new BadRequest('O campo {' . $field . '} não existe no modelo ' . get_class($this->getModel()) . '!');
        // }

        return $field;
    }
    public function equal(string $field, string $value, int $options = 0) : self
    {
        // $this->filter[$field] = $value;
        $this->parseCriteria('$eq', $field, $value, $options);
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
        $this->parseCriteria('EXISTS', null, $value, $options);
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
        $this->parseCriteria('>', $field, $value, $options);
        // $filter = [
        //     '$gt' => $this->parseValue($field, $value)
        // ];

        // $this->filter[$field] = $filter;
        return $this;
    }
    public function greaterEqual(string $field, $value, int $options = 0) : self
    {
        // $filter = [
        //     '$gte' => +$value
        // ];

        // $this->filter[$field] = $filter;
        $this->parseCriteria('>=', $field, $value, $options);
        return $this;
    }
    public function in(string $field, array $values, int $options = 0) : self
    {
        $this->parseCriteria('$in', $field, $values, $options);
        // $filter = [
        //     '$in' => implode(',', $values)
        // ];

        // $this->filter[$field] = $filter;
        return $this;
    }
    public function isNotNull(string $field, int $options = 0) : self
    {
        $this->parseCriteria('IS NOT NULL', $field, null, $options);
        return $this;
    }
    public function isNull(string $field, int $options = 0) : self
    {
        $this->parseCriteria('IS NULL', $field, null, $options);
        return $this;
    }
    public function less(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('<', $field, $value, $options);
        return $this;
    }
    public function lessEqual(string $field, $value, int $options = 0) : self
    {
        $this->parseCriteria('<=', $field, $value, $options);
        return $this;
    }
    public function like(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('LIKE', $field, $value, $options);
        return $this;
    }
    public function match(string $field, string $value, int $options = 0) : self
    {
        // \MonitoLib\Dev::pre($field);
        // \MonitoLib\Dev::pre($value);

        $f = explode('.', $field);

        $filter = [
            '$elemMatch' => [
                $f[1] => +$value
            ]
        ];


        $this->filter[$f[0]] = $filter;

        return $this;
    }
    public function notEqual(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('<>', $field, $value, $options);
        return $this;
    }
    public function notExists(string $field, int $options = 0) : self
    {
        // $f = explode('.', $field);

        $filter = [
            '$exists' => false
        ];

        $this->filter[$field] = $filter;
        return $this;
    }
    public function notIn(string $field, array $values, int $options = 0) : self
    {
        $this->parseCriteria('NOT IN', $field, $values, $options);
        return $this;
    }
    public function notLike(string $field, string $value, int $options = 0) : self
    {
        $this->parseCriteria('NOT LIKE', $field, $value, $options);
        return $this;
    }
    public function orderBy($field, $direction = 'ASC', $modifiers = 0) : self
    {
        $this->checkField($field, $modifiers);

        $this->orderBy[$field] = strtoupper($direction) === 'DESC' ? -1 : 1;
        return $this;
    }
    private function parseCriteria(string $comparisonOperator, $field, $value, int $options = 0)
    {
        $f = $this->checkField($field);

        // \MonitoLib\Dev::ee('???');
        $options    = $this->parseOptions($options);
        $fixedQuery = $options->fixedQuery;
        $checkNull  = $options->checkNull;
        $rawQuery   = $options->rawQuery;
        $operator   = $options->operator;
        $startGroup = $options->startGroup;
        $endGroup   = $options->endGroup;

        $type   = $f->getType();
        // $name   = null;
        // $format = null;

        // \MonitoLib\Dev::pr($type);

        // \MonitoLib\Dev::pre($value);

        // \MonitoLib\Dev::ee($comparisonOperator);

        if ($comparisonOperator !== '$in') {
            switch ($type) {
                case 'int':
                    $value = (int)$value;
                    break;
                // default:
                    // $value = '/' . $value . '/';
            }
        }


        // switch ($comparisonOperator) {
        //     case '>':
        //         $method = '$gt';
        //         break;
        //     case '<':
        //         $method = '$lt';
        //         break;
        //     case '>=':
        //         $method = '$gte';
        //         break;
        //     case '<=':
        //         $method = '$lte';
        //         break;
        //     case '<>':
        //     case '!':
        //         $method = '$ne';
        //         break;
        //     default:
        //         $method = '$eq';
        //         break;
        // }


        // $this->filter[$field] = [
        //     $method => $value
        // ];

        // \MonitoLib\Dev::pre($filter);


        // if (!isset($this->filter[$field])) {
        //     $this->filter[$field] = [];
        // }

        // \MonitoLib\Dev::vd($value);

        $this->filter[$field][$comparisonOperator] = $value;

        // \MonitoLib\Dev::pre($this->filter);


        // return $this;
    }
    public function parseFilter($fieldName, $value, $type = 'string') : self
    {
        $field = $this->checkField($fieldName);
        $type  = $field['type'];
        $parsedValue = urldecode($value);

        $hasNegation  = false;
        $hasQualifier = false;
        $hasEqualizer = false;
        $operator     = 'eq';

        // Verifica se o valor tem negação
        if ($parsedValue[0] === '!') {
            $hasNegation = $parsedValue[0] === '!';
            $parsedValue = substr($parsedValue, 1);
            $operator    = 'ne';
        }

        // Verifica se tem qualificador
        if (in_array($qualifier = $parsedValue[0], ['<', '>'])) {
            $hasQualifier = true;
            $parsedValue  = substr($parsedValue, 1);

            if ($qualifier === '<') {
                $operator = 'lt';

                if ($hasNegation) {
                    $operator = 'gt';
                }
            } else {
                $operator = 'gt';

                if ($hasNegation) {
                    $operator = 'lt';
                }
            }

            // Verifica se tem equalizador
            if ($parsedValue[0] === '=') {
                $hasEqualizer = true;
                $parsedValue  = substr($parsedValue, 1);
                $operator     .= 'e';
            }
        }

        // Verifica se o valor é nulo
        if ($parsedValue === "\x00") {
            // $isNull = true;
            $parsedValue = null;
        } else {
            // Verifica se o valor númerico é valido
            if (in_array($type, ['double', 'float', 'int'])) {
                if (!is_numeric($parsedValue)) {
                    throw new BadRequest("Valor inválido para um campo numérico: $parsedValue");
                }

                $parsedValue = +$parsedValue;
            }

            // Verifica se a string tem limitadores
            if ($type === 'string') {
                if ($parsedValue[0] === '%') {
                    $parsedValue = '*.' . $parsedValue;
                }

                if (substr($parsedValue, -1, 1) === '%') {
                    $parsedValue = substr($parsedValue, 0, -1) . '.*';
                }

                $parsedValue = "/$parsedValue/i";
            }
        }

        $operator = '$' . $operator;

        $this->parseCriteria($operator, $fieldName, $parsedValue);

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

    public function renderFilter() : array
    {
        // \MonitoLib\Dev::pre($this->criteria);
        // \MonitoLib\Dev::pr($this->filter);
        // \MonitoLib\Dev::pre($this);
        // $filter = [];

        // $filter = [
        //     'skus' => [
        //         '$elemMatch' => [
        //             'sku' =>  3110
        //         ]
        //     ]
        // ];

        // \MonitoLib\Dev::pre($this->filter);


        return $this->filter;
    }
    public function renderFindOne() : array
    {
        $filter  = $this->renderFilter();
        $options = [];//$this->renderOptions();

        return [
            $filter,
            $options
        ];
    }
    public function renderOptions() : array
    {
        $options = [];

        if (!empty($this->fields)) {
            $options['projection'] = $this->fields;
        }

        if (!empty($this->orderBy)) {
            $sort = [];

            foreach ($this->orderBy as $k => $v) {
                $sort[$k] = $v;
            }

            $options['sort'] = $sort;
        }

        if ($this->page > 1) {
            $options['skip'] = ($this->page - 1) * $this->perPage;
        }

        if ($this->perPage > 0) {
            $options['limit'] = $this->perPage;
        }

        // \MonitoLib\Dev::pre($options);

        return $options;
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
        $this->filter        = [];
        return $this;
    }
    public function setDbms(int $dbms) : self
    {
        $this->dbms = $dbms;
        return $this;
    }
    public function setFields(?array $fields = []) : self
    {
        if (empty($fields)) {
            return $this;
        }

        $this->fields = [];

        foreach ($fields as $f) {
            $this->fields[$f] = true;
        }

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
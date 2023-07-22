<?php
/**
 * Database\Query\MongoDB.
 *
 * @version 1.2.0
 */

namespace MonitoLib\Database\Query;

use MonitoLib\Exception\BadRequestException;

class MongoDB extends \MonitoLib\Database\Dao\Base
{
    // Options flags
    public const FIXED_QUERY = 1;
    public const CHECK_NULL = 2;
    public const RAW_QUERY = 4;
    public const OR = 8;
    public const START_GROUP = 16;
    public const END_GROUP = 32;

    protected $fields = [];
    protected $convertName = true;
    protected $countCriteria;

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
    private $page = 1;
    private $perPage = 0;
    private $reseted = false;
    private $selectedFields;
    private $selectSql;
    private $selectSqlReady = false;
    private $sql;
    private $sqlCount;
    private $filter = [];

    public function between(string $field, $value1, $value2, int $options = 0): self
    {
        $this->parseCriteria('>=', $field, $value1, $options);
        $this->parseCriteria('<=', $field, $value2, $options);

        return $this;
    }

    public function bit(string $field, int $value, int $options = 0): self
    {
        $this->parseCriteria('&', $field, $value, $options);

        return $this;
    }

    public function count()
    {
        return 0;
    }

    public function equal(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('$eq', $field, $value, $options);

        return $this;
    }

    public function exists(string $value, int $options = 0): self
    {
        $this->parseCriteria('EXISTS', null, $value, $options);

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
        $this->parseCriteria('>', $field, $value, $options);

        return $this;
    }

    public function greaterEqual(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('>=', $field, $value, $options);

        return $this;
    }

    public function in(string $field, array $values, int $options = 0): self
    {
        $this->parseCriteria('$in', $field, $values, $options);

        return $this;
    }

    public function isNotNull(string $field, int $options = 0): self
    {
        $this->parseCriteria('IS NOT NULL', $field, null, $options);

        return $this;
    }

    public function isNull(string $field, int $options = 0): self
    {
        $this->parseCriteria('IS NULL', $field, null, $options);

        return $this;
    }

    public function list()
    {
        return [];
    }

    public function less(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('<', $field, $value, $options);

        return $this;
    }

    public function lessEqual(string $field, $value, int $options = 0): self
    {
        $this->parseCriteria('<=', $field, $value, $options);

        return $this;
    }

    public function like(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('LIKE', $field, $value, $options);

        return $this;
    }

    public function match(string $field, string $value, int $options = 0): self
    {
        $f = explode('.', $field);

        $filter = [
            '$elemMatch' => [
                $f[1] => +$value,
            ],
        ];

        $this->filter[$f[0]] = $filter;

        return $this;
    }

    public function notEqual(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('<>', $field, $value, $options);

        return $this;
    }

    public function notExists(string $field, int $options = 0): self
    {
        $filter = [
            '$exists' => false,
        ];

        $this->filter[$field] = $filter;

        return $this;
    }

    public function notIn(string $field, array $values, int $options = 0): self
    {
        $this->parseCriteria('NOT IN', $field, $values, $options);

        return $this;
    }

    public function notLike(string $field, string $value, int $options = 0): self
    {
        $this->parseCriteria('NOT LIKE', $field, $value, $options);

        return $this;
    }

    public function orderBy($field, $direction = 'ASC', $modifiers = 0): self
    {
        $this->checkField($field, $modifiers);

        $this->orderBy[$field] = strtoupper($direction) === 'DESC' ? -1 : 1;

        return $this;
    }

    public function parseFilter($fieldName, $value, $type = 'string'): self
    {
        $field = $this->checkField($fieldName);
        $type = $field['type'];
        $parsedValue = urldecode($value);

        $hasNegation = false;
        $operator = 'eq';

        // Verifica se o valor tem negação
        if ($parsedValue[0] === '!') {
            $hasNegation = $parsedValue[0] === '!';
            $parsedValue = substr($parsedValue, 1);
            $operator = 'ne';
        }

        // Verifica se tem qualificador
        if (in_array($qualifier = $parsedValue[0], ['<', '>'])) {
            $parsedValue = substr($parsedValue, 1);

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

            if ($parsedValue[0] === '=') {
                $parsedValue = substr($parsedValue, 1);
                $operator .= 'e';
            }
        }

        // Check if the value is null
        if ($parsedValue === "\x00") {
            $parsedValue = null;
        } else {
            if (in_array($type, ['double', 'float', 'int'])) {
                if (!is_numeric($parsedValue)) {
                    throw new BadRequestException("Invalid number: {$parsedValue}");
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

                $parsedValue = "/{$parsedValue}/i";
            }
        }

        $operator = '$' . $operator;

        $this->parseCriteria($operator, $fieldName, $parsedValue);

        return $this;
    }

    public function renderFilter(): array
    {
        return $this->filter;
    }

    public function renderFindOne(): array
    {
        $filter = $this->renderFilter();
        $options = [];

        return [
            $filter,
            $options,
        ];
    }

    public function renderOptions(): array
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

        return $options;
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
        $this->filter = [];

        return $this;
    }

    public function setDbms(int $dbms): self
    {
        $this->dbms = $dbms;

        return $this;
    }

    public function setFields(?array $fields = []): self
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

    private function checkField(string $field, bool $rawQuery = false): \MonitoLib\Database\Model\Field
    {
        if ($rawQuery) {
            return $field = [
                'name' => $field,
                'type' => 'string',
                'format' => null,
            ];
        }

        $fields = explode('.', $field);
        $model = $this->model;

        foreach ($fields as $f) {
            $field = $model->getField($f);
            $type = $field->getType();

            if (class_exists($type)) {
                $model = str_replace('\\Dto\\', '\\Model\\', $type);
                $model = new $model();
            } else {
                break;
            }
        }

        return $field;
    }

    private function parseCriteria(string $comparisonOperator, $field, $value, int $options = 0)
    {
        $f = $this->checkField($field);
        $type = $f->getType();

        if ($comparisonOperator !== '$in') {
            switch ($type) {
                case 'int':
                    $value = (int) $value;
                    break;
            }
        }

        $this->filter[$field][$comparisonOperator] = $value;
    }
}

<?php
namespace MonitoLib\Database;

// use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Database\Query\Filter;

class Query
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-07-08
    * Initial release
    */

    // Options flags
    const NONE        = 0;
    const FIXED       = 1;
    const CHECK_NULL  = 2;
    const RAW_QUERY   = 4;
    const OR          = 8;
    const START_GROUP = 16;
    const END_GROUP   = 32;

    private $filter;

    public function between(string $field, $value1, $value2, int $options = self::NONE) : self
    {
        $this->parseWhere(Filter::BETWEEN, $field, $value1, $value2, $options);
        return $this;
    }
    public function bitAnd(string $field, int $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::BIT_AND, $field, $value, null, $options);
        return $this;
    }
    public function equal(string $field, string $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::EQUAL, $field, $value, null, $options);
        return $this;
    }
    public function exists(string $value, int $options = 0) : self
    {
        return $this;
    }
    public function columns(array $fields = null) : self
    {
        $this->initFilter();
        $this->filter->setColumns($fields);
        return $this;
    }
    public function getFilter() : \MonitoLib\Database\Query\Filter
    {
        $this->initFilter();
        return $this->filter;
    }
    public function greater(string $field, $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::GREATER, $field, $value, null, $options);
        return $this;
    }
    public function greaterEqual(string $field, $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::GREATER_EQUAL, $field, $value, null, $options);
        return $this;
    }
    public function groupBy(array $fields, int $options = 0) : self
    {
        $this->query['fields'] = $fields;
        return $this;
    }
    public function having(string $field, int $options = 0) : self
    {
        return $this;
    }
    public function in(string $field, array $values, int $options = 0) : self
    {
        $this->parseWhere(Filter::IN, $field, $values, null, $options);
        return $this;
    }
    private function initFilter() : void
    {
        if (is_null($this->filter)) {
            $this->filter = new \MonitoLib\Database\Query\Filter();
        }
    }
    public function less(string $field, $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::LESS, $field, $value, null, $options);
        return $this;
    }
    public function lessEqual(string $field, $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::LESS_EQUAL, $field, $value, null, $options);
        return $this;
    }
    public function like(string $field, string $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::LIKE, $field, $value, null, $options);
        return $this;
    }
    public function map(array $map) : self
    {
        $this->initFilter();
        $this->filter->setMap($map);
        return $this;
    }
    public function notEqual(string $field, string $value, int $options = self::NONE) : self
    {
        $this->parseWhere(Filter::NOT_EQUAL, $field, $value, null, $options);
        return $this;
    }
    public function notExists(string $value, int $options = 0) : self
    {
        return $this;
    }
    public function notIn(string $field, array $values, int $options = 0) : self
    {
        $this->parseWhere(Filter::NOT_IN, $field, $values, null, $options);
        return $this;
    }
    public function notLike(string $field, string $value, int $options = 0) : self
    {
        $this->parseWhere(Filter::NOT_LIKE, $field, $value, null, $options);
        return $this;
    }
    public function notNull(string $field, int $options = 0) : self
    {
        $this->parseWhere(Filter::IS_NOT_NULL, $field, null, null, $options);
        return $this;
    }
    public function null(string $field, int $options = 0) : self
    {
        $this->parseWhere(Filter::IS_NULL, $field, null, null, $options);
        return $this;
    }
    public function orderBy($column, $direction = 'ASC', $options = 0) : self
    {
        $options = new \MonitoLib\Database\Query\Options($options);
        $isRaw   = $options->isRaw();
        $isRaw   = $isRaw ? $isRaw : is_int($column);

        $this->initFilter();
        $this->filter->addOrderBy($column, $direction);
        return $this;
    }
    public function page(int $page) : self
    {
        $this->initFilter();
        $this->filter->setPerPage($page);
        return $this;
    }
    private function parseWhere(string $comparisonOperator, ?string $column, $value1, $value2 = null, int $options = 0)
    {
        $options = new \MonitoLib\Database\Query\Options($options);
        // \MonitoLib\Dev::pre($options);

        // $where = $this->parseGroup($options->startGroup(), $options->endGroup());
        $value = $value1;

        if (!is_null($value2)) {
            $value = [$value1, $value2];
        }

        // $options = new \MonitoLib\Database\Query\Options($options);
        // $isRaw   = $options->isRaw();

        // if (!$isRaw) {
        //     // Valida o campo no modelo
        //     $column = $this->checkColumn($column);
        //     $type   = $column->getType();
        //     $format = $column->getFormat();
        // }

        $where = new \MonitoLib\Database\Query\Filter\Where();
        $where
            ->setColumn($column)
            ->setComparison($comparisonOperator)
            ->setValue($value)
            // ->setType($type)
            // ->setFormat($format)
            ->setOptions($options);

        $this->initFilter();
        $this->filter->addWhere($where);

        // array_push($this->query, $where);
    }
    public function perPage(int $perPage) : self
    {
        $this->initFilter();
        $this->filter->setPerPage($perPage);
        return $this;
    }
    public function mergeFilter(\MonitoLib\Database\Query\Filter $filter)
    {
        $this->filter = $filter;
    }
    public function reset()
    {
        $this->filter = null;
    }
    public function set(string $column, $value, int $options = self::NONE) : self
    {
        $options = new \MonitoLib\Database\Query\Options($options);
        // \MonitoLib\Dev::pre($options);

        // $where = $this->parseGroup($options->startGroup(), $options->endGroup());
        // $value = $value1;

        // if (!is_null($value2)) {
        //     $value = [$value1, $value2];
        // }

        // $options = new \MonitoLib\Database\Query\Options($options);
        // $isRaw   = $options->isRaw();

        // if (!$isRaw) {
        //     // Valida o campo no modelo
        //     $column = $this->checkColumn($column);
        //     $type   = $column->getType();
        //     $format = $column->getFormat();
        // }

        $set = new \MonitoLib\Database\Query\Filter\Set();
        $set
            ->setColumn($column)
            ->setValue($value)
            ->setOptions($options);

        $this->initFilter();
        $this->filter->addSet($set);
        return $this;
    }
}
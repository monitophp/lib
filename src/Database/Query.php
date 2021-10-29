<?php

namespace MonitoLib\Database;

use \MonitoLib\Database\Query\Filter;
use \MonitoLib\Database\Query\Options;

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

    public function between(string $field, array $values, int $options = self::NONE): self
    {
        $this->parseWhere(Filter::BETWEEN, $field, $values, $options);
        return $this;
    }
    public function bitAnd(string $field, int $value, int $options = 0): self
    {
        $this->parseWhere(Filter::BIT_AND, $field, $value, $options);
        return $this;
    }
    public function equal(string $field, ?string $value, int $options = 0): self
    {
        $this->parseWhere(Filter::EQUAL, $field, $value, $options);
        return $this;
    }
    // public function exists(string $value, int $options = 0): self
    // {
    //     return $this;
    // }
    public function columns(array $fields = null): self
    {
        $this->initFilter();
        $this->filter->setColumns($fields);
        return $this;
    }
    public function getFilter(): Filter
    {
        $this->initFilter();
        return $this->filter;
    }
    public function greater(string $field, $value, int $options = 0): self
    {
        $this->parseWhere(Filter::GREATER, $field, $value, $options);
        return $this;
    }
    public function greaterEqual(string $field, $value, int $options = 0): self
    {
        $this->parseWhere(Filter::GREATER_EQUAL, $field, $value, $options);
        return $this;
    }
    public function groupBy(array $fields, int $options = 0): self
    {
        $this->query['fields'] = $fields;
        return $this;
    }
    // public function having(string $field, int $options = 0): self
    // {
    //     return $this;
    // }
    public function in(string $field, array $values, int $options = 0): self
    {
        $this->parseWhere(Filter::IN, $field, $values, $options);
        return $this;
    }
    private function initFilter(): void
    {
        if (is_null($this->filter)) {
            $this->filter = new Filter();
        }
    }
    public function less(string $field, $value, int $options = 0): self
    {
        $this->parseWhere(Filter::LESS, $field, $value, $options);
        return $this;
    }
    public function lessEqual(string $field, $value, int $options = 0): self
    {
        $this->parseWhere(Filter::LESS_EQUAL, $field, $value, $options);
        return $this;
    }
    public function like(string $field, string $value, int $options = 0): self
    {
        $this->parseWhere(Filter::LIKE, $field, $value, $options);
        return $this;
    }
    public function map(array $map): self
    {
        $this->initFilter();
        $this->filter->setMap($map);
        return $this;
    }
    public function notEqual(string $field, string $value, int $options = self::NONE): self
    {
        $this->parseWhere(Filter::NOT_EQUAL, $field, $value, $options);
        return $this;
    }
    // public function notExists(string $value, int $options = 0): self
    // {
    //     return $this;
    // }
    public function notIn(string $field, array $values, int $options = 0): self
    {
        $this->parseWhere(Filter::NOT_IN, $field, $values, $options);
        return $this;
    }
    public function notLike(string $field, string $value, int $options = 0): self
    {
        $this->parseWhere(Filter::NOT_LIKE, $field, $value, $options);
        return $this;
    }
    public function notNull(string $field, int $options = 0): self
    {
        $this->parseWhere(Filter::IS_NOT_NULL, $field, null, $options);
        return $this;
    }
    public function null(string $field, int $options = 0): self
    {
        $this->parseWhere(Filter::IS_NULL, $field, null, $options);
        return $this;
    }
    public function orderBy(string $column, string $direction = 'ASC', int $options = 0): self
    {
        $options = new Options($options);
        $isRaw   = $options->isRaw();
        $isRaw   = $isRaw ? $isRaw : is_int($column);

        $this->initFilter();
        $this->filter->addOrderBy($column, $direction);
        return $this;
    }
    public function page(int $page): self
    {
        $this->initFilter();
        $this->filter->setPerPage($page);
        return $this;
    }
    private function parseWhere(string $comparisonOperator, ?string $column, $value, int $options = 0): void
    {
        $options = new Options($options);

        if (is_null($value)) {
            $comparisonOperator = Filter::IS_NULL;
        }

        $where = new Filter\Where();
        $where
            ->setColumn($column)
            ->setComparison($comparisonOperator)
            ->setValue($value)
            ->setOptions($options);

        $this->initFilter();
        $this->filter->addWhere($where);
    }
    public function perPage(int $perPage): self
    {
        $this->initFilter();
        $this->filter->setPerPage($perPage);
        return $this;
    }
    public function mergeFilter(Filter $filter)
    {
        $this->filter = $filter;
    }
    public function reset(): self
    {
        $this->filter = null;
        return $this;
    }
    public function set(string $column, $value, int $options = self::NONE): self
    {
        $options = new Options($options);

        $set = new Filter\Set();
        $set
            ->setColumn($column)
            ->setValue($value)
            ->setOptions($options);

        $this->initFilter();
        $this->filter->addSet($set);
        return $this;
    }
}

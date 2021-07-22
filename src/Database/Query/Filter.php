<?php
namespace MonitoLib\Database\Query;

class Filter
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-07-09
    * Initial release
    */

    const BETWEEN         = 'BETWEEN';
    const BIT_AND         = '&';
    const BIT_LEFT_SHIFT  = '<<';
    const BIT_NOT         = '~';
    const BIT_OR          = '|';
    const BIT_RIGHT_SHIFT = '>>';
    const BIT_XOR         = '^';
    const EQUAL           = '=';
    const GREATER         = '>';
    const GREATER_EQUAL   = '>=';
    const IN              = 'IN';
    const IS_NOT_NULL     = 'IS NOT NULL';
    const IS_NULL         = 'IS NULL';
    const LESS            = '<';
    const LESS_EQUAL      = '<=';
    const LIKE            = 'LIKE';
    const NOT_EQUAL       = '<>';
    const NOT_IN          = 'NOT IN';
    const NOT_LIKE        = 'NOT LIKE';

    private $page    = 1;
    private $perPage = 0;
    private $columns = [];
    private $map     = [];
    private $where   = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having  = [];

    public function addOrderBy(string $column, string $direction)
    {
        $this->orderBy[$column] = $direction;
        return $this;
    }
    public function addWhere(\MonitoLib\Database\Query\Filter\Where $where)
    {
        $this->where[] = $where;
    }
    public function getColumns() : array
    {
        return $this->columns;
    }
    public function getOrderBy() : array
    {
        return $this->orderBy;
    }
    public function getMap() : array
    {
        return $this->map;
    }
    public function getPage() : int
    {
        return $this->page;
    }
    public function getPerPage() : int
    {
        return $this->perPage;
    }
    public function setMap(array $map) : self
    {
        $this->map = $map;
        return $this;
    }
    public function setPage(int $page) : self
    {
        $this->page = $page;
        return $this;
    }
    public function setPerPage(int $perPage) : self
    {
        $this->perPage = $perPage;
        return $this;
    }
    public function getWhere() : array
    {
        return $this->where;
    }
    public function setColumns(array $columns) : self
    {
        $this->columns = $columns;
        return $this;
    }
}
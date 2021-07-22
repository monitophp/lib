<?php
namespace MonitoLib\Database\MongoDB;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Validator;

class Dml
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-03-04
    * Initial release
    */

    private $dbms;
    private $filter;
    private $model;
    private $dao;
    private $insertColumns = [];

    public function __construct(\MonitoLib\Database\Model $model, int $dbms, \MonitoLib\Database\Query\Filter $filter)
    {
        $this->model  = $model;
        $this->dbms   = $dbms;
        $this->filter = $filter;
    }
    public function getInsertColumns() : array
    {
        if (empty($this->insertColumns)) {
            $insertColumns = [];

            // foreach ($this->model->getColumns() as $column) {
            //     if (!$column->getPrimary() || (!$column->getAuto() && !is_null($column->getSource()))) {
            //         $insertColumns[] = $column->getName();
            //     }
            // }

            $this->insertColumns = array_filter($this->model->getColumns(), function($column) {
                if (!$column->getPrimary() || (!$column->getAuto() && !is_null($column->getSource()))) {
                    return $column;
                }
            });

            // $this->insertColumns = $insertColumns;
        }

        // \MonitoLib\Dev::pre($this->insertColumns);

        return $this->insertColumns;
    }
    public function parseFilter($where) : array
    {
        $name         = $where->getColumn();
        $type         = $where->getType();
        $comparison   = $where->getComparison();
        $value        = $where->getValue();
        $options      = $where->getOptions();
        // $isFixedQuery = $options->isFixedQuery();
        // $checkNull  = $options->getCheckNull();
        // $rawQuery   = $options->getRawQuery();
        $operator   = $options->getOperator();

        // $field = $this->checkField($fieldName);
        // $type  = $field['type'];
        // $parsedValue = urldecode($value);

        $hasNegation  = false;
        $hasQualifier = false;
        $hasEqualizer = false;
        $operator     = 'eq';

        // // Verifica se o valor tem negação
        // if ($parsedValue[0] === '!') {
        //     $hasNegation = $parsedValue[0] === '!';
        //     $parsedValue = substr($parsedValue, 1);
        //     $operator    = 'ne';
        // }

        // // Verifica se tem qualificador
        // if (in_array($qualifier = $parsedValue[0], ['<', '>'])) {
        //     $hasQualifier = true;
        //     $parsedValue  = substr($parsedValue, 1);

        //     if ($qualifier === '<') {
        //         $operator = 'lt';

        //         if ($hasNegation) {
        //             $operator = 'gt';
        //         }
        //     } else {
        //         $operator = 'gt';

        //         if ($hasNegation) {
        //             $operator = 'lt';
        //         }
        //     }

        //     // Verifica se tem equalizador
        //     if ($parsedValue[0] === '=') {
        //         $hasEqualizer = true;
        //         $parsedValue  = substr($parsedValue, 1);
        //         $operator     .= 'e';
        //     }
        // }

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
    public function renderFilter(?bool $onlyFixed = false) : array
    {
        $filter    = $this->filter;
        $whereList = $filter->getWhere();
        $filterArray = [];

        $model = $this->model;

        foreach ($whereList as $where) {
            $name         = $where->getColumn();
            $type         = $where->getType();
            $comparison   = $where->getComparison();
            $value        = $where->getValue();
            $options      = $where->getOptions();
            $isFixed = $options->isFixed();
            // $checkNull  = $options->getCheckNull();
            // $rawQuery   = $options->getRawQuery();
            $operator   = $options->getOperator();

            if ($onlyFixed && !$isFixed) {
                continue;
            }

            $column = $model->getColumn($name);
            $type = $column->getType();

            // \MonitoLib\Dev::vde($type);

            if (in_array($type, ['int','double','float'])) {
                $value = +$value;
            }

            // \MonitoLib\Dev::pre($where);

            switch ($comparison) {
                case '=':
                    $comparison = '$eq';
                    break;
                case '<':
                    $comparison = '$lt';
                    break;
                case '>':
                    $comparison = '$gt';
                    break;
            }

            if (!isset($filterArray[$name])) {
                $filterArray[$name] = [];
            }

            $filterArray[$name][$comparison] = $value;
        }

        // if ($this->whereString === '') {

            // if (!empty($whereList)) {

            //     $this->whereString = ' WHERE' . preg_replace('/^ (AND|OR)/', '', $this->whereString);
            // }
        // }

        // $filterArray = [
        //     'vtexId' => [
        //         '$gt' => 1,
        //         '$lt' => 16,
        //     ]
        // ];

        // \MonitoLib\Dev::pr($filterArray);


        return $filterArray;
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


        // return $this->filter;
    }
    public function renderFindOne() : array
    {
        $filter  = $this->renderFilter();
        $options = $this->renderOptions();

        return [
            $filter,
            $options
        ];
    }
    public function renderOptions() : array
    {
        $filter = $this->filter;
        $options = [];

        if (!empty($filter->getColumns())) {
            $options['projection'] = $filter->getColumns();
        }

        if (!empty($filter->getOrderBy())) {
            $sort = [];

            foreach ($filter->getOrderBy() as $k => $v) {
                $sort[$k] = $v;
            }

            $options['sort'] = $sort;
        }

        if ($filter->getPage() > 1) {
            $options['skip'] = ($filter->getPage() - 1) * $filter->getPerPage();
        }

        if ($filter->getPerPage() > 0) {
            $options['limit'] = $filter->getPerPage();
        }

        // \MonitoLib\Dev::pre($options);

        return $options;
    }
}
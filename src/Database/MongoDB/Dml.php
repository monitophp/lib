<?php
namespace MonitoLib\Database\MongoDB;

use MonitoLib\Dev;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;
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
    private $dtos = [];

    public function __construct(\MonitoLib\Database\Model $model, int $dbms, \MonitoLib\Database\Query\Filter $filter)
    {
        $this->model  = $model;
        $this->dbms   = $dbms;
        $this->filter = $filter;
        $this->dtos['root'] = [
            'dto' => str_replace('\\Model', '\\Dto', get_class($model)),
            'model' => $model
        ];
    }
    public function getDtoName(string $index) : array
    {
        // \MonitoLib\Dev::pre($this->dtos);
        $dto = $this->dtos[$index] ?? [];

        // if (is_null($dto)) {
        //     throw new NotFound("Dto $index not found");
        // }

        return $dto;
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
    private function parseColumns(array $columns) : array
    {
        $mapx = [];

        foreach ($columns as $m) {
            $position = strpos($m, '.');

            if ($position === false) {
                $mapx[$m] = 1;
            } else {
                $column = substr($m, 0, $position);
                // \MonitoLib\Dev::e($column);

                if (!isset($mapx[$column])) {
                    $mapx[$column] = [];
                }

                $value  = substr($m, $position + 1);

                $value  = [$value];

                // \MonitoLib\Dev::pr($value);

                // $value  = explode('.', $value);
                $value  = $this->parseColumns($value);
                $ecaoc  = $mapx[$column] ?? [];

                // \MonitoLib\Dev::pr($ecaoc);
                // \MonitoLib\Dev::pr($value);
                // $merged = array_merge_recursive($ecaoc, $value);
                $mapx[$column] = array_merge_recursive($ecaoc, $value);

                // // $merged = Functions::arrayMergeRecursive($mapx, [$column => $value]);
                // // $merged = array_combine([$column => $value], $mapx);
                // \MonitoLib\Dev::pr($merged);
            }
        }

        // Aqui vai botar o dto no array

        return $mapx;
    }
    private function parseDto(object $model, array $columns, ?string $columnName = 'root') : void
    {
        $nven = [];

        if (empty($columns)) {
            $this->dtos['root'] = [
                'dto'   => $model->getDtoName(),
                'model' => get_class($model),
            ];

            $columns = $model->getColumns();

            foreach ($columns as $column) {
                $id = $column->getId();
                $type = $column->getType();
                $valueType = gettype($type);

                if ($valueType === 'array') {
                    $type = $type[0];
                }

                $isClass = class_exists($type);

                // \MonitoLib\Dev::e("$type: $valueType");

                switch ($isClass) {
                    case 'object':
                        $this->dtos[$id] = [
                            'dto'   => $type,
                            'model' => str_replace('\\Dto', '\\Model', $type),
                        ];
                }
            }

        } else {
            foreach ($columns as $key => $value) {
                $isArray = is_array($value);

                if ($isArray) {
                    $column  = $model->getColumn($key);
                    $type    = $column->getType();
                    $modelName = str_replace('\\Dto', '\\Model', $type);

                    // \MonitoLib\Dev::e($modelName);

                    $modelx = new $modelName();

                    // \MonitoLib\Dev::e($model);

                    // \MonitoLib\Dev::pre($column);

                    $this->parseDto($modelx, $value, $key);
                }

                $nven[] = $key;
            }

            // Compara com as colunas para ver se é igual
            $xyz = $model->getColumnIds();
            $modelName = get_class($model);

            $dh = serialize($nven);
            $mh = serialize($xyz);

            if ($dh === $mh) {
                $dto = str_replace('\\Model', '\\Dto', $modelName);
            } else {
                $dto = \MonitoLib\Database\Dto::get($nven, true);
            }

            // \MonitoLib\Dev::pr($nven);
            // \MonitoLib\Dev::pre($xyz);

            $this->dtos[$columnName] = [
                'dto'   => $dto,
                'model' => $modelName,
            ];
        }

        // \MonitoLib\Dev::pre($this->dtos);

        // \MonitoLib\Dev::pre($columns);

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
        $filter = $this->filter;

        // \MonitoLib\Dev::pre($filter);

        $whereList = $filter->getWhere();
        $filterArray = [];

        $model = $this->model;

        foreach ($whereList as $where) {
            $name       = $where->getColumn();
            $type       = $where->getType();
            $comparison = $where->getComparison();
            $value      = $where->getValue();
            $options    = $where->getOptions();
            $isFixed    = $options->isFixed();
            // $checkNull  = $options->getCheckNull();
            // $isRaw      = $options->getRawQuery();
            $operator   = $options->getOperator();

            if ($onlyFixed && !$isFixed) {
                continue;
            }

            $column = $model->getColumn($name);
            $type   = $column->getType();
            $name   = $column->getName();

            // \MonitoLib\Dev::vde($type);

            if (in_array($type, ['int','double','float'])) {
                $value = +$value;
            }

            if ($type === 'oid') {
                // $value = new \MongoDB\
                $value = new \MongoDB\BSON\ObjectID($value);

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
        $options = [];
        $filter  = $this->filter;
        $columns = $filter->getColumns();
        $model   = $this->model;

        if (!empty($columns)) {
            // $columns =
        }

        $columnss = $this->parseColumns($columns);

        // \MonitoLib\Dev::pre($columnss);

        $this->parseDto($this->model, $columnss);

            // \MonitoLib\Dev::pre($this->dtos);

            // foreach ($columns as $id => $value) {
            //     $isArray = is_array($value);
            // }
        // }

        // \MonitoLib\Dev::pre($this->dtos);

        // \MonitoLib\Dev::pre($this->model);
        $columns = array_flip($columns);

        // $_model        = $this->model;
        // $_modelColumns = array_map(function($e) {
        //     return $e->getId();
        // }, $_model->getColumns());

        // \MonitoLib\Dev::pr($map);
        // \MonitoLib\Dev::pre($_modelColumns);

        // $_mapColumns = array_keys((array)$document);

        // \MonitoLib\Dev::pr($_mapColumns);
        // \MonitoLib\Dev::pre($_modelColumns);

        $columns = array_map(function($v) {
            return $v = 1;
        }, $columns);

















        if (!empty($columns)) {
            // $columns = array_map(function($e) use ($model) {
            //     return $model->getColumn($e)->getName();
            // }, $columns);

            $projection = [
                '_id' => 0
            ];

            $projection = array_merge($projection, $columns);

            // \MonitoLib\Dev::pre($projection);

            // foreach ($columns as $column) {
            //     $projection[$column] = 1;
            // }

            $options['projection'] = $projection;
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

        // \MonitoLib\Dev::pre($this->dtos);
        // \MonitoLib\Dev::pre($options);

        return $options;
    }
}
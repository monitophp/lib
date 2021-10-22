<?php

namespace MonitoLib\Database\Query;

use \MonitoLib\Database\Model;
use \MonitoLib\Database\Query\Filter;
use \MonitoLib\Database\Query\Filter\Where;
use \MonitoLib\Database\Query\Options;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class Parser
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2021-07-09
     * Initial release
     */

    private $query;

    public function parse(
        ?string $queryString,
        ?Model $model = null,
        ?\MonitoLib\Database\Dao $dao = null
    ): Filter
    {
        $fields = is_null($queryString) ? [] : explode('&', $queryString);
        $filter = new Filter();

        foreach ($fields as $field) {
            $p = strpos($field, '=');
            $f = substr($field, 0, $p);
            $v = urldecode(substr($field, $p + 1));
            $x = strtolower($f);

            switch ($x) {
                case 'fields';
                    $filter = $filter->setColumns(explode(',', $v));
                    break;
                case 'page';
                    $filter = $filter->setPage($v);
                    break;
                case 'perpage';
                    $filter = $filter->setPerPage($v);
                    break;
                case 'orderby';
                    $filter = $filter->addOrderBy($v, 'ASC');
                    break;
                default:
                    $filter = $this->parseFilter($filter, $f, $v, $model);
            }
        }

        return $filter;
    }
    public function getColumn(Model $model, array $columnsName): \MonitoLib\Database\Model\Column
    {
        $column = $model->getColumn($columnsName[0]);
        array_shift($columnsName);

        if (!empty($columnsName)) {
            $type  = $column->getType();
            $model = str_replace('\\Dto', '\\Model', $type);

            if (!class_exists($model)) {
                throw new NotFound("Class $model not found");
            }

            $model  = new $model();
            $column = $this->getColumn($model, $columnsName);
        }

        return $column;
    }
    private function parseFilter($filter, string $field, ?string $value, ?object $model = null)
    {
        $type = 'string';
        $comparison = '=';

        if (!is_null($model)) {
            // Identifica a coluna no modelo
            $column = $this->getColumn($model, explode('.', $field));
            $type   = $column->getType();
            $field  = $column->getName();
        }

        $modifier = $this->parseModifier($type, $value);

        if (in_array($type, ['double', 'float', 'int'])) {
            $value = $this->parseNumberValue($value);
        } else {
            $value = $this->parseStringValue($value);
        }

        // \MonitoLib\Dev::pr($value);

        if (in_array($type, ['date', 'datetime', 'time'])) {
            // $regex = '/((\d{4}-\d{2}-\d{2})(?:[ tT](?:\d{2}:\d{2}:\d{2}))?(?:\.(?:\d{6}))?)-((\d{4}-\d{2}-\d{2})(?:[ tT](?:\d{2}:\d{2}:\d{2}))?(?:\.(?:\d{6}))?)/';
            $regex = '/((\d{4}(-\d{2}){2})?(?:[ tT]?(?:\d{2}(:\d{2}){2}))?(?:\.(?:\d{6}))?)-((\d{4}(-\d{2}){2})?(?:[ tT]?(?:\d{2}(:\d{2}){2}))?(?:\.(?:\d{6}))?)/';

            if (preg_match($regex, $value, $m)) {
                $modifier   = '-';
                $value      = [$m[1], $m[5]];
                $comparison = 'BETWEEN';
                // \MonitoLib\Dev::pre($m);
            }
        } else {

            switch ($modifier) {
                case '-':
                    $comparison = 'BETWEEN';
                    $value      = explode('-', $value);
                    break;
                case ',':
                    $comparison = 'IN';
                    $value      = explode(',', $value);
                    break;
                case '!':
                    // break;
                case '>':
                case '<':
                case '>=':
                case '<=':
                    $value = preg_replace("/^$modifier/", '', $value);
                    $comparison = $modifier;
                    break;
            }
        }

        $options = new Options(0);
        $where = new Where();
        $where
            ->setColumn($field)
            ->setComparison($comparison)
            ->setValue($value)
            ->setType($type)
            ->setOptions($options);
        $filter->addWhere($where);

        return $filter;
    }
    private function parseModifier($type, $value): string
    {
        $modifier = '';
        $regex    = '';

        switch ($type) {
            case 'date':
            case 'datetime':
            case 'time':
                $regex = '/(^[!><=]+)?((\d{4}-\d{2}-\d{2})(?:[ tT](?:\d{2}:\d{2}:\d{2}))?(?:\.(?:\d{6}))?)/';
                $index = 1;
                break;
            case 'double':
            case 'float':
            case 'int':
                $regex = '(^[^0-9.]+|[-,])';
                $index = 0;
                break;
        }

        if ($regex !== '') {
            if (preg_match($regex, $value, $m)) {
                $modifier = $m[$index];
            }
        }

        return $modifier;
    }
    private function parseDate($field, $value)
    {
        if (preg_match('/(^[!><=]+)?((\d{4}-\d{2}-\d{2})(?:[ tT](?:\d{2}:\d{2}:\d{2}))?(?:\.(?:\d{6}))?)/', $value, $m)) {
            $modifier = $m[1];
            $value    = $m[2];


            switch ($modifier) {
                case '':
                    $this->query->equal($field, $value);
                    break;
                case '>':
                    $this->query->greater($field, $value);
                    break;
                case '<':
                    $this->query->less($field, $value);
                    break;
                case '>=':
                    $this->query->greaterEqual($field, $value);
                    break;
                case '<=':
                    $this->query->lessEqual($field, $value);
                    break;
                case '!':
                    $this->query->notEqual($field, $value);
                    break;
                default:
                    throw new BadRequest("Invalid modifier $modifier for $field");
            }
        }

        return $filter;
    }
    private function parseNumber($field, $value)
    {
        $method = 'equal';
        $value2 = null;

        if (is_numeric($value)) {
            \MonitoLib\Dev::vde($value);
            $this->query->equal($field, +$value);
        } else {
            // Verifica se tem algum modificador
            if (preg_match('/(^[^0-9.]+|[-,])/', $value, $m)) {
                $modifier = $m[0];

                if (in_array($modifier, ['>', '<', '>=', '<=', '!'])) {
                    $value = +str_replace($modifier, '', $value);
                }

                switch ($modifier) {
                    case '-':
                        $values = explode('-', $value);
                        $this->query->between($field, +$values[0], +$values[1]);
                        break;
                    case ',':
                        $values = explode(',', $value);
                        $values = array_map(function ($e) {
                            return +$e;
                        }, $values);
                        $this->query->in($field, $values);
                        break;
                    case '>':
                        $this->query->greater($field, +$value);
                        break;
                    case '<':
                        $this->query->less($field, +$value);
                        break;
                    case '>=':
                        $this->query->greaterEqual($field, +$value);
                        break;
                    case '<=':
                        $this->query->lessEqual($field, +$value);
                        break;
                    case '!':
                        $this->query->notEqual($field, +$value);
                        break;
                }
            }
        }
    }
    private function parseNumberValue(string $value)
    {
        return $value;
    }
    private function parseString($field, $value)
    {
        $parts = explode(' ', $value);

        foreach ($parts as $p) {
            $this->query->like("UPPER($field)", "UPPER('%$p%')", self::RAW_QUERY);
        }
    }
    private function parseStringValue(string $value)
    {
        return $value;
    }
    public function parseWhere(string $comparisonOperator, ?string $column, $value1, $value2 = null, int $options = 0)
    {
        // $options = new \MonitoLib\Database\Query\Options($options);
        // $where = $this->parseGroup($options->startGroup(), $options->endGroup());
        $value = $value1;

        if (!is_null($value2)) {
            $value = [$value1, $value2];
        }

        $options = new Options($options);
        $isRaw   = $options->isRaw();

        if (!$isRaw) {
            // Valida o campo no modelo
            $column = $this->checkColumn($column);
            $type   = $column->getType();
            $format = $column->getFormat();
        }

        $where = new Where();
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
}

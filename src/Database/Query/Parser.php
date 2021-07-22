<?php
namespace MonitoLib\Database\Query;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
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
        ?\MonitoLib\Database\Model $model = null,
        ?\MonitoLib\Database\Dao $dao = null
    ) : \MonitoLib\Database\Query\Filter
    {
        $fields = is_null($queryString) ? [] : explode('&', $queryString);
        $filter = new \MonitoLib\Database\Query\Filter();

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


            // if (!$p && $field === 'ds') {
            //     self::$asDataset = true;
            // } else {
                // if (strcasecmp($f, 'fields') === 0) {
                // } elseif (strcasecmp($f, 'page') === 0) {
                // } elseif (strcasecmp($f, 'perpage') === 0) {
                // } elseif (strcasecmp($f, 'orderby') === 0) {
                // } else {
                // }
            // }
        // $f      = $this->checkField($field);
        // $type   = $f['type'];
        // $format = $f['format'];


        switch ($type) {
            case 'date':
            case 'datetime':
                break;
            case 'double':
            case 'float':
            case 'int':

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

        // return $this;
    }
    private function parseFilter($filter, string $field, ?string $value, ?object $model = null)
    {
        $type = 'string';
        $comparison = '=';

        if (!is_null($model)) {
            // Identifica a coluna no modelo
            $column = $model->getColumn($field);
            $type   = $column->gettype();
        }

        $modifier = $this->parseModifier($type, $value);

        if (in_array($type, ['double', 'float', 'int'])) {
            $value = $this->parseNumberValue($value);
        } else {
            $value = $this->parseStringValue($value);
        }

        // \MonitoLib\Dev::pr($value);

        if (in_array($type, ['date','datetime','time'])) {
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

        // $values = explode(',', $value);
        // $values = array_map(function($e) {
        //     return +$e;
        // }, $values);

        // $values = explode('-', $value);

        $options = new \MonitoLib\Database\Query\Options(0);
        $where = new \MonitoLib\Database\Query\Filter\Where();
        $where
            ->setColumn($column->getName())
            ->setComparison($comparison)
            ->setValue($value)
            ->setType($type)
            // ->setFormat($format)
            ->setOptions($options);
        $filter->addWhere($where);

        // \MonitoLib\Dev::vde($type);

        // \MonitoLib\Dev::pre($column);

        // \MonitoLib\Dev::pre($model);
        return $filter;
    }
    private function parseModifier($type, $value) : string
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

        if (preg_match($regex, $value, $m)) {
            $modifier = $m[$index];
        }

        return $modifier;
    }
    private function parseDate($field, $value)
    {
        if (preg_match('/(^[!><=]+)?((\d{4}-\d{2}-\d{2})(?:[ tT](?:\d{2}:\d{2}:\d{2}))?(?:\.(?:\d{6}))?)/', $value, $m)) {
            // \MonitoLib\Dev::pr($m);
            $modifier = $m[1];
            // \MonitoLib\Dev::vde($modifier);
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

        // \MonitoLib\Dev::pre($value);
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

                if (in_array($modifier, ['>','<','>=','<=','!'])) {
                    $value = +str_replace($modifier, '', $value);
                }

                switch ($modifier) {
                    case '-':
                        $values = explode('-', $value);
                        $this->query->between($field, +$values[0], +$values[1]);
                        break;
                    case ',':
                        $values = explode(',', $value);
                        $values = array_map(function($e) {
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

        $options = new \MonitoLib\Database\Query\Options($options);
        $isRaw   = $options->isRaw();

        if (!$isRaw) {
            // Valida o campo no modelo
            $column = $this->checkColumn($column);
            $type   = $column->getType();
            $format = $column->getFormat();
        }

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
}
<?php
namespace MonitoLib\Database;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class Model
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2019-06-05
    * new: model name in validate thrown error message
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    protected $constraints;
    protected $tableType = 'table';
    protected $fieldDefaults = [
        'auto'      => false,
        'source'    => null,
        'type'      => 'string',
        'format'    => null,
        'charset'   => 'utf8',
        'collation' => 'utf8_general_ci',
        'default'   => null,
        'label'     => '',
        'maxLength' => 0,
        'minLength' => 0,
        'maxValue'  => 0,
        'minValue'  => 0,
        'precision' => null,
        'scale'     => null,
        'primary'   => false,
        'required'  => false,
        'transform' => null,
        'unique'    => false,
        'unsigned'  => false,
    ];
    protected $fieldsInsert;

    public function getUniqueConstraints ()
    {
        return $this->constraints['unique'];
    }
    public function getField ($field)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field];
        }
    }
    public function getFieldName ($field)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field]['name'];
        }
    }
    public function getFields ()
    {
        $fields = $this->fields;

        $func = function ($fields) {
            $f = Functions::arrayMergeRecursive($this->fieldDefaults, $fields);

            if ($f['type'] === 'date' && is_null($f['format'])) {
                $f['format'] = 'Y-m-d';
            }

            if (!is_null($f['transform'])) {
                $transform = explode(',', $f['transform']);
                $insert = ':' . $f['name'];

                foreach ($transform as $t) {
                    $insert = $t . '(' . $insert . ')';
                }

                $f['transform'] = $insert;
            }

            return $f;
        };

        $fields = array_map($func, $fields);

        return $fields;
    }
    // Retorna string com campos da tabela separados por vírgula, ignorando campos de auto incremento
    public function getFieldsInsert ()
    {
        if (is_null($this->fieldsInsert)) {
            $func = function ($value) {
                if (!$value['primary'] || !is_null($value['auto'])) {
                    return true;
                }
            };

            $this->fieldsInsert = array_filter($this->getFields(), $func);
        }

        return $this->fieldsInsert;
    }
    // Retorna array com lista dos campos da tabela
    public function getFieldsList ()
    {
        return array_keys($this->fields);
    }
    public function getName ()
    {
        $class = get_class($this);
        return substr($class, strrpos($class, '\\') + 1);
    }
    public function getPrimaryKeys ()
    {
        return $this->keys;
    }
    public function getPrimaryKey ()
    {
        $keys = 'id';

        if (!is_null($this->keys)) {
            $keys = null;

            foreach ($this->keys as $k) {
                $keys .= "$k,";
            }

            $keys = substr($keys, 0, -1);
        }

        return $keys;
    }
    public function listFieldsNames ()
    {
        $list = [];

        foreach ($this->fields as $key => $value) {
            if (isset($value['name'])) {
                $list[] = $value['name'];
            } else {
                $list[] = $key;
            }
        }

        return $list;
    }
    public function getTableName ()
    {
        return $this->tableName;
    }
    public function getTableType ()
    {
        return $this->tableType;
    }
    public function validate ($dto)
    {
        $errors = [];

        $fields = $this->getFields();

        foreach ($fields as $fk => $fv) {
            $get       = 'get' . ucfirst($fk);
            $value     = $dto->$get();
            $label     = $fv['label'];
            $auto      = $fv['auto'];
            $type      = $fv['type'];
            $format    = $fv['format'];
            $required  = $fv['required'];
            $default   = $fv['default'];
            $maxLength = $fv['maxLength'];
            $minLength = $fv['minLength'];
            $maxValue  = $fv['maxValue'];
            $minValue  = $fv['minValue'];
            $length    = mb_strlen($value);
            $vType     = gettype($value);

            if (is_null($value) || $value === '') {
                // Verifica se um campo requerido foi informado
                if ($required) {
                    if (!$auto && ((is_null($value) || $value === '') && is_null($default))) {
                        $errors[] = "O campo {$label} é requerido!";
                    }
                }
            } else {
                // Verifica se o campo é do tipo esperado
                if ($type === 'int' || $type === 'double') {
                    if ($type === 'int' && !is_numeric($value) && !$auto) {
                        $errors[] = "O campo {$label} espera um número inteiro e {$vType} foi informado!";
                    }

                    if ($type === 'float' && !is_float($value)) {
                        $errors[] = "O campo {$label} espera um número decimal e {$vType} foi informado!";
                    }

                    if (is_numeric($value)) {
                        // Verifica o valor máximo do campo
                        if ($maxValue > 0 && $value > $maxValue) {
                            $errors[] = "O valor máximo do campo {$label} é {$maxValue} mas {$value} foi informado!";
                        }

                        // Verifica o tamanho mínimo do campo
                        if ($minValue > 0 && $value > $minValue) {
                            $errors[] = "O tamanho mínimo do campo {$label} é {$minValue} mas {$value} foi informado!";
                        }
                    }
                }

                if ($type === 'date' && !Validator::date($value, $format)) {
                    if ($format === 'Y-m-d') {
                        $errors[] = "Data inválida para o campo {$label}! $value";
                    } else {
                        $errors[] = "Data/hora inválida para o campo {$label}! $value";
                    }
                }

                // Verifica o tamanho máximo do campo
                if ($maxLength > 0 && $length > $maxLength) {
                    $errors[] = "O tamanho máximo do campo {$label} é {$maxLength} mas {$length} foi informado!";
                }

                // Verifica o tamanho mínimo do campo
                if ($minLength > 0 && $length < $minLength) {
                    $errors[] = "O tamanho mínimo do campo {$label} é {$minLength} mas {$length} foi informado!";
                }
            }
        }

        if (!empty($errors)) {
            throw new BadRequest('Não foi possível validar os dados do model ' . get_class($this) . '!', $errors);
        }
    }
}
<?php
/**
 * Database\Model.
 *
 * @version 1.0.2
 */

namespace MonitoLib\Database;

use MonitoLib\Exception\InvalidModelException;
use MonitoLib\Validator;

class Model
{
    protected $constraints;
    protected $tableType = 'table';
    protected $fields;
    protected $keys;
    protected $tableName;
    protected $fieldDefaults = [
        'auto' => false,
        'source' => null,
        'type' => 'string',
        'format' => null,
        'charset' => 'utf8',
        'collation' => 'utf8_general_ci',
        'default' => null,
        'label' => '',
        'maxLength' => 0,
        'minLength' => 0,
        'maxValue' => 0,
        'minValue' => 0,
        'precision' => null,
        'scale' => null,
        'primary' => false,
        'required' => false,
        'transform' => null,
        'unique' => false,
        'unsigned' => false,
    ];
    protected $fieldsInsert;
    private $parsedFields = [];

    public function getUniqueConstraints()
    {
        return $this->constraints['unique'] ?? null;
    }

    public function getField($field)
    {
        if (!isset($parsedFields)) {
            $this->parsedFields[$field] = $this->parseField($field);
        }

        return $this->parsedFields[$field];
    }

    public function getFieldName($field)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field]['name'];
        }
    }

    public function getFields()
    {
        $fields = $this->fields;

        $func = function ($fields) {
            $f = ml_array_merge_recursive($this->fieldDefaults, $fields);

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

        return array_map($func, $fields);
    }

    public function getFieldsInsert()
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

    public function getFieldsList()
    {
        return array_keys($this->fields);
    }

    public function getName()
    {
        $class = get_class($this);

        return substr($class, strrpos($class, '\\') + 1);
    }

    public function getPrimaryKeys()
    {
        return $this->keys;
    }

    public function getPrimaryKey()
    {
        $keys = 'id';

        if (!is_null($this->keys)) {
            $keys = null;

            foreach ($this->keys as $k) {
                $keys .= "{$k},";
            }

            $keys = substr($keys, 0, -1);
        }

        return $keys;
    }

    public function listFieldsNames()
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

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getTableType()
    {
        return $this->tableType;
    }

    public function validate(object $dto)
    {
        $errors = [];

        $fields = $this->getFields();

        foreach ($fields as $fk => $fv) {
            $get = 'get' . ucfirst($fk);
            $value = $dto->{$get}();
            $label = $fv['label'];
            $auto = $fv['auto'];
            $type = $fv['type'];
            $format = $fv['format'];
            $required = $fv['required'];
            $default = $fv['default'];
            $maxLength = $fv['maxLength'];
            $minLength = $fv['minLength'];
            $maxValue = $fv['maxValue'];
            $minValue = $fv['minValue'];
            $length = is_string($value) ? mb_strlen($value) : 0;
            $vType = gettype($value);

            if (is_null($value) || $value === '') {
                if ($required) {
                    if (!$auto && ((is_null($value) || $value === '') && is_null($default))) {
                        $errors[] = "Field {$fk} is required";
                    }
                }
            } else {
                if ($type === 'int' || $type === 'double') {
                    if ($type === 'int' && !is_numeric($value) && !$auto) {
                        $errors[] = "Field {$fk} expects an integer and {$vType} given";
                    }

                    if ($type === 'float' && !is_float($value)) {
                        $errors[] = "Field {$fk} expects a flot and {$vType} given";
                    }

                    if (is_numeric($value)) {
                        if ($maxValue > 0 && $value > $maxValue) {
                            $errors[] = "Max value for {$fk} is {$maxValue} and {$value} given";
                        }

                        if ($minValue > 0 && $value < $minValue) {
                            $errors[] = "Min value for {$fk} is {$minValue} and {$value} given";
                        }
                    }
                }

                if ($type === 'date' && !Validator::date($value, $format)) {
                    if ($format === 'Y-m-d') {
                        $errors[] = "Invalid date for field {$fk}: {$value}";
                    } else {
                        $errors[] = "Invalid datetime for field {$fk}: {$value}";
                    }
                }

                if ($maxLength > 0 && $length > $maxLength) {
                    $errors[] = "Max length for {$fk} is {$minLength} and {$length} given";
                }

                if ($minLength > 0 && $length < $minLength) {
                    $errors[] = "Min length for {$fk} is {$minLength} and {$length} given";
                }
            }
        }

        if (!empty($errors)) {
            throw new InvalidModelException('Invalid model ' . get_class($this) . ': ' . implode(' | ', $errors), $errors);
        }
    }

    private function parseField(string $fieldName)
    {
        if (!isset($this->fields[$fieldName])) {
            return [];
        }

        $field = new \MonitoLib\Database\Model\Field();
        $field->setId($fieldName)
            ->setName($fieldName)
        ;

        foreach ($this->fields[$fieldName] as $property => $value) {
            $set = 'set' . ucfirst($property);
            $field->{$set}($value);
        }

        return $field;
    }
}

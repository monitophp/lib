<?php
namespace MonitoLib\Database;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class Model
{
    private const VERSION = '1.0.1';
    /**
    * 1.0.2 - 2021-05-04
    * new: getField return
    *
    * 1.0.1 - 2019-06-05
    * new: model name in validate thrown error message
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

    public const ARRAY = 'array';
    public const BOOL = 'bool';
    public const OID = 'oid';
    public const CHAR = 'char';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const FLOAT = 'float';
    public const INT = 'int';
    public const STRING = 'string';
    public const TIME = 'time';

    public const NOW = 'now';
    public const TODAY = 'today';
    public const USER_ID = 'userId';

    protected $daoClass;
    protected $fields = [];
    private $parsedFields = [];
    private $insertString;
    private $dtoName;

    public function __construct()
    {
        if (is_null($this->daoClass)) {
            $classname = get_class($this);

            $this->daoClass = Functions::getNamespace($classname, 2)
                . 'Dao\\'
                . Functions::getClassname($classname);
        }
    }
	/**
	* getColumn
	*
	* @return $fieldName | null
	*/
    public function getColumn(string $fieldName, ?bool $raw = false) : ?\MonitoLib\Database\Model\Column
    {
        if (!isset($this->columns[$fieldName])) {
            throw new BadRequest("Column $fieldName not found in model " . get_class($this));
        }

        if ($raw) {
            return $this->columns[$fieldName];
        }

        return $this->parsedFields[$fieldName] ?? $this->parseField($fieldName, $this->columns[$fieldName]);
    }
	/**
	* getColumns
	*
	* @return array $columns
	*/
    public function getColumns(?bool $raw = false) : array
    {
        if ($raw) {
            return $this->columns;
        }

        if (empty($this->parsedFields)) {
            foreach ($this->columns as $id => $field) {
                $this->parsedFields[] = $this->parseField($id, $field);
            }
        }

        return $this->parsedFields;
    }
	/**
	* getFields
	*
	* @return array $fields
	*/
    public function getColumnIds(?bool $useAlias = false) : array
    {
        return array_map(function($e) use ($useAlias) {
            return $useAlias ? $e->getAlias() : $e->getId();
        }, $this->getColumns());
    }
	/**
	* getDaoName
	*
	* @return string $daoName
	*/
    public function getDaoName() : string
    {
        if (is_null($this->daoName)) {
            $classname = get_class($this);

            $this->daoName = Functions::getNamespace($classname, 2)
                . 'Dto\\'
                . Functions::getClassname($classname);
        }

        return $this->daoName;
    }
	/**
	* getDtoName
	*
	* @return string $dtoName
	*/
    public function getDtoName() : string
    {
        if (is_null($this->dtoName)) {
            // $classname = get_class($this);
            $this->dtoName = str_replace('\\Model', '\\Dto', get_class($this));

            // $this->dtoName = Functions::getNamespace($classname, 1)
            //     . 'Dto\\'
            //     . Functions::getClassname($classname);
        }

        // \MonitoLib\Dev::ee($this->dtoName);

        return $this->dtoName;
    }
	/**
	* getInsertFields
	*
	* @return string getInsertFields
	*/
    public function getInsertColumnsArray() : array
    {
        return array_filter($this->getColumns(), function($column) {
            if (!$column->getPrimary() || (!$column->getAuto() && !is_null($column->getSource()))) {
                return $column;
            }
        });


        $columns = $this->getColumns();
        $insertArray = [];

        foreach ($columns as $column) {
            if (!$column->getPrimary() || (!$column->getAuto() && !is_null($column->getSource()))) {
                $insertArray[] = $column->getId();
            }
        }

        return $insertArray;


        return array_map(function($e) {
            if (!$e->getPrimary() || (!$e->getAuto() && !is_null($e->getSource()))) {
                return $e->getId();
            }
        }, $this->getColumns());

        if (is_null($this->insertArray)) {
            $insertArray = [];

            array_map(function($e) use ($insertArray) {
                if (!$e->getPrimary() || (!$e->getAuto() && !is_null($e->getSource()))) {
                    $insertArray[] = $e->getId();
                }
            }, $this->getColumns());

            $this->insertArray[] = $insertArray;
        }

        return $this->insertArray;
    }
	/**
	* getInsertFields
	*
	* @return string getInsertFields
	*/
    public function getInsertColumns() : string
    {
        if (is_null($this->insertString)) {
            $insertString = '';

            array_map(function($e) use ($insertString) {
                if (!$e->getPrimary() || (!$e->getAuto() && !is_null($e->getSource()))) {
                    $insertString .= $e->getId() . ',';
                }
            }, $this->getColumns());

            $this->insertString = $insertString;
        }

        return $this->insertString;
    }
	/**
	* getInsertValues
	*
	* @return string getInsertValues
	*/
    public function getInsertValues() : string
    {
        if (is_null($this->insertString)) {
            $insertString = '';

            array_map(function($e) use ($insertString) {
                if (!$e->getPrimary() || (!$e->getAuto() && !is_null($e->getSource()))) {
                    $insertString .= $e->getId() . ',';
                }
            }, $this->getFields());

            $this->insertString = $insertString;
        }

        return $this->insertString;
    }
	/**
	* getPrimaryKeys
	*
	* @return string getPrimaryKeys
	*/
    public function getPrimaryKeys() : array
    {
        return array_filter($this->getColumns(), function($column) {
            if ($column->getPrimary()) {
                return $column;
            }
        });
    }
	/**
	* parseField
	*
	* @return \MonitoLib\Database\Model\Field $field
	*/
    private function parseField(string $id, array $field) : \MonitoLib\Database\Model\Column
    {
        // if (!isset($this->fields[$fieldName])) {
        //     throw new BadRequest("O campo $fieldName não existe no modelo");
        // }

        $fieldObj = new \MonitoLib\Database\Model\Column();
        $fieldObj->setId($id)
            ->setName($id);

        foreach ($field as $property => $value) {
            $set = 'set' . ucfirst($property);

            if (!method_exists($fieldObj, $set)) {
                throw new \Exception("Invalid field property: $property");
            }

            $fieldObj->$set($value);
        }

        return $fieldObj;
    }
	/**
	* getTableName
	*
	* @return string $tableName
	*/
    public function getTableName() : string
    {
        return $this->table['name'];
    }
	/**
	* getTableType
	*
	* @return string $tableType
	*/
    public function getTableType() : string
    {
        return $this->table['type'] ?? 'table';
    }







// $this->model->getFields()
// $this->model->getFields()

// $this->model->getFieldsInsert()
// $this->model->getFieldsInsert()

// $this->model->getPrimaryKeys();
// $this->model->getPrimaryKeys();

// $this->model->getTableName()
// $this->model->getTableName()

// $this->model->getTableType()

// $this->model->getUniqueConstraints(),
// $this->model->getUniqueConstraints(),

// $this->model->validate($dto
// $this->model->validate($dto





























//     protected $constraints;
//     protected $tableType = 'table';
//     protected $fieldsInsert;
//     private $parsedFields = [];

//     public function getUniqueConstraints()
//     {
//         return $this->constraints['unique'] ?? null;
//     }
//     public function getField($field)
//     {
//         if (!isset($parsedFields)) {
//             $this->parsedFields[$field] = $this->parseField($field);
//         }

//         return $this->parsedFields[$field];
//     }
//     public function getFieldName($field)
//     {
//         if (isset($this->fields[$field])) {
//             return $this->fields[$field]['name'];
//         }
//     }
//     public function getFields()
//     {
//         $fields = $this->fields;

//         $func = function ($fields) {
//             $f = Functions::arrayMergeRecursive($this->fieldDefaults, $fields);

//             if ($f['type'] === 'date' && is_null($f['format'])) {
//                 $f['format'] = 'Y-m-d';
//             }

//             if (!is_null($f['transform'])) {
//                 $transform = explode(',', $f['transform']);
//                 $insert = ':' . $f['name'];

//                 foreach ($transform as $t) {
//                     $insert = $t . '(' . $insert . ')';
//                 }

//                 $f['transform'] = $insert;
//             }

//             return $f;
//         };

//         $fields = array_map($func, $fields);

//         return $fields;
//     }
//     // Retorna string com campos da tabela separados por vírgula, ignorando campos de auto incremento
//     public function getFieldsInsert()
//     {
//         if (is_null($this->fieldsInsert)) {
//             $func = function ($value) {
//                 if (!$value['primary'] || !is_null($value['auto'])) {
//                     return true;
//                 }
//             };

//             $this->fieldsInsert = array_filter($this->getFields(), $func);
//         }

//         return $this->fieldsInsert;
//     }
//     // Retorna array com lista dos campos da tabela
//     public function getFieldsList()
//     {
//         return array_keys($this->fields);
//     }
//     public function getName()
//     {
//         $class = get_class($this);
//         return substr($class, strrpos($class, '\\') + 1);
//     }
//     public function getPrimaryKeys()
//     {
//         return $this->keys;
//     }
//     public function getPrimaryKey()
//     {
//         $keys = 'id';

//         if (!is_null($this->keys)) {
//             $keys = null;

//             foreach ($this->keys as $k) {
//                 $keys .= "$k,";
//             }

//             $keys = substr($keys, 0, -1);
//         }

//         return $keys;
//     }
//     public function listFieldsNames()
//     {
//         $list = [];

//         foreach ($this->fields as $key => $value) {
//             if (isset($value['name'])) {
//                 $list[] = $value['name'];
//             } else {
//                 $list[] = $key;
//             }
//         }

//         return $list;
//     }
//     public function getTableName()
//     {
//         return $this->tableName;
//     }
//     public function getTableType()
//     {
//         return $this->tableType;
//     }
//     private function parseField(string $fieldName)
//     {
//         if (!isset($this->fields[$fieldName])) {
//             return [];
//             // throw new BadRequest("O campo $fieldName não existe no modelo");
//         }

//         $field = new \MonitoLib\Database\Model\Field();
//         $field->setId($fieldName)
//             ->setName($fieldName);

//         foreach ($this->fields[$fieldName] as $property => $value) {
//             $set = 'set' . ucfirst($property);
//             $field->$set($value);
//         }

//         // $field
//         //     ->setAuto($auto)
//         //     ->setSource($source)
//         //     ->setType($type)
//         //     ->setFormat($format)
//         //     ->setCharset($charset)
//         //     ->setCollation($collation)
//         //     ->setDefault($default)
//         //     ->setLabel($label)
//         //     ->setMaxLength($maxLength)
//         //     ->setMinLength($minLength)
//         //     ->setMaxValue($maxValue)
//         //     ->setMinValue($minValue)
//         //     ->setPrecision($precision)
//         //     ->setScale($scale)
//         //     ->setPrimary($primary)
//         //     ->setRequired($required)
//         //     ->setTransform($transform)
//         //     ->setUnique($unique)
//         //     ->setUnsigned($unsigned);

//         return $field;
//     }
}
# Query

## Constantes

## FIXED_QUERY
Define que o parâmetro passado para o SQL será fixo para efeito de COUNT
## CHECK_NULL
Verifica se o valor no banco de dados é igual ao passado ou nulo
## RAW_QUERY
Não faz verificações ou tratamentos no valor passado

## Métodos
### andBitAnd ($field, $value, $modifiers = 0)

### equal
Params: `(string $field, mix $value, int $options = 0)`\
Alias: `eq`
```php
$pessoaDao->equal('id', 1)
```
### andFilter ($field, $value, $type = 'string')
### andGreaterEqual ($field, $value, $modifiers = 0)
### andGreaterThan ($field, $value, $modifiers = 0)
### andIn ($field, $values, $modifiers = 0)
### andIsNotNull ($field, $modifiers = 0)
### andIsNull ($field, $modifiers = 0)
### andLessEqual ($field, $value, $modifiers = 0)
### andLessThan ($field, $value, $modifiers = 0)
### andLike ($field, $value, $modifiers = 0)
### andNotEqual ($field, $value, $modifiers = 0)
### andNotIn ($field, $values, $modifiers = 0)
### andNotLike ($field, $value, $modifiers = 0)
### endGroup ($modifiers = 0)
### getModelFields () {
### getOrderBySql ()
### getPage ()
### getPerPage ()
### getWhereSql ($fixed = false)
### orBitAnd ($field, $value, $modifiers = 0)
### orderBy ($field, $direction = 'ASC', $modifiers = 0)
### orEqual ($field, $value, $modifiers = 0)
### orIsNotNull ($field, $modifiers = 0)
### orIsNull ($field, $modifiers = 0)
### orLessEqual ($field, $value, $modifiers = 0)
### orLessThan ($field, $value, $modifiers = 0)
### orNotEqual ($field, $value, $modifiers = 0)
### renderCountSql ($all = false)
### renderDeleteSql ()
### renderSelectSql ()
### reset ()
### setDbms ($dbms)
### setFields ($fields) {
### setModel ($model) {
### setOrderBy ($orderBy)
### setPage ($page)
### setPerPage ($perPage)
### setQuery ($query)
### setSql ($sql)
### setSqlCount ($sqlCount)
### setTableName ($tableName) {
### startAndGroup ($modifiers = 0)
### startGroup ($modifiers = 0)
### startOrGroup ($modifiers = 0)

<?php
namespace MonitoLib\Database\Dao;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Functions;
use \MonitoLib\Validator;

class
{
    const VERSION = '1.3.0';
    /**
    * 1.3.0 - 2019-12-09
    * new: andNotIn
    * fix: several fixes
    *
    * 1.2.0 - 2019-10-23
    * fix: several fixes
    *
    * 1.1.3 - 2019-06-05
    * fix: removed checkIfFieldExists from setQuery
    *
    * 1.1.2 - 2019-05-05
    * fix: getSelectFields parameter on dataset method
    * fix: checkIfFieldExists in all query methods
    *
    * 1.1.1 - 2019-05-03
    * new: getSelectFields checks format
    *
    * 1.1.0 - 2019-05-02
    * new: removed parseRequest
    * fix: CHECK_NULL constant name
    *
    * 1.0.0 - 2019-04-07
    * First versioned
    */

    const FIXED_QUERY = 1;
    const CHECK_NULL  = 2;
    const RAW_QUERY   = 4;

    const DB_MYSQL  = 1;
    const DB_ORACLE = 2;

    private $criteria;
    private $fixedCriteria;
    private $reseted = false;

    private $selectedFields;

    private $page    = 1;
    private $perPage = 0;
    private $orderBy = [];
    private $sql;
    private $sqlCount;

    private $selectSql;
    private $selectSqlReady = false;
    private $countSql;
    private $countSqlReady = false;
    private $orderBySql;
    private $orderBySqlReady = false;

    private $modelFields;



    private function addCriteriaParser ($logicalOperator, $comparisonOperator, $field, $value, $modifiers = 0)
    private function checkIfFieldExists ($field, $modifiers = 0)
    private function escape ($value) {
    private function getLimitSql ()
    protected function getSelectFields ($format = true)





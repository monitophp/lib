# Consultas ao banco de dados
## between(string $field, $value1, $value2, int $options = 0)
  Retorna valores dentro de um intervado

## bit(string $field, int $value, int $options = 0)

## equal(string $field, string $value, int $options = 0)

## exists(string $value, int $options = 0)

## greater(string $field, $value, int $options = 0)

## greaterEqual(string $field, $value, int $options = 0)

## in(string $field, array $values, int $options = 0)
## isNotNull(string $field, int $options = 0)
## isNull(string $field, int $options = 0)
## less(string $field, $value, int $options = 0)
## lessEqual(string $field, $value, int $options = 0)
## like(string $field, string $value, int $options = 0)
## notEqual(string $field, string $value, int $options = 0)
## notExists(string $value, int $options = 0)
## notIn(string $field, array $values, int $options = 0)
## notLike(string $field, string $value, int $options = 0)
## orderBy($field, $direction = 'ASC', $modifiers = 0)
## setFields(array $fields = null)
## setPage(int $page)
## setPerPage(int $perPage)


## Opções
  É possível passar algumas opções para modificar as consultas

  ::ALL
  Adiciona o modificador ALL na consulta

  ::ANY
  Adiciona o modificador ANY na consulta

  ::CHECK_NULL
  Indica que além da comparação feita pelo método vai também verificar se a coluna está nulo na tabela.

  ::FIXED_QUERY
  Usando para indicar na consulta do tipo `dataset` que o filtro será usado na contagem total dos registros.

  ::RAW_QUERY
  Por padrão os nomes dos campos e valores são checados antes de executar a consulta ao banco de dados. Com o modificador `RAW_QUERY` é possível ignorar as validações padrão.

  ::OR
  Modifica a ligação com o campo ANTERIOR

  ::START_GROUP
  Inicia um grupo INCLUINDO o campo atual

  ::END_GROUP
  Fecha um grupo após o campo atual




















## getModelFields() : ?array
## getPage() : int
## getPerPage() : int

## setDbms(int $dbms) : self
## reset() : self
## setQuery(?array $query) : self
## setSql(string $sql) : self
## setSqlCount(string $sqlCount) : self
## setTableName(string $tableName) : self
## setMap(array $map, bool $convertName = true) : self
## setModel(string $model) : self


## setOrderBy(?array $orderBy) : self

## renderCountSql(bool $all = false) : string
## renderDeleteSql() : string
## renderOrderBySql() : string
## renderSelectSql() : string
## renderWhereSql(bool $fixed = false) : string




## parseFilter($field, $value, $type = 'string') : self
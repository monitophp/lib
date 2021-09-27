# Modelo de dados
A manipulação de dados na MonitoLib se dá através das classes DAO - Data Access Object - de cada tabela.

## Propriedades do modelo
O modelo de dados tem as propriedades `$table`, `$columns`, ??


## Coluna
Uma coluna no modelo é o conjunto de suas propriedades. Nenhuma opção da coluna é obrigatória, assumindo seu valor padrão no caso de abstenção.

### id
Índice do array que identifica a coluna no banco de dados, no padrão `camelCase`.

### name
Nome do campo no banco de dados. Obrigatório se diferente do id da coluna.\
Tipo de dado: `string`\
Valor padrão: `id da coluna`

Exemplo 1:
```php
private $columns = [
    ...
    'productName' => [ // <- identificador da tabela em camelCase
        'name': 'product_name', // <- nome da tabela no banco de dados
    ],
    ...
];
```

### auto
Indica se a coluna recebe um valor automaticamente.\
Tipo de dado: `bool`\
Valor padrão: `false`

### default
Valor padrão do campo, caso receba valor nulo ao ser inserido ou alterado.\
Tipo de dado: `array|bool|float|int|string`\
Valor padrão: `null`

Exemplo 1:
```php
private $columns = [
    ...
    'productName' => [
        'default' 'Test',
    ],
    ...
];
```

Exemplo 2:
```php
private $columns = [
    ...
    'productValue' => [
        'default' 12.12
    ],
    ...
];
```

Exemplo 3:
```php
private $columns = [
    ...
    'createdBy' => [
        'default' => [
            'onInsert' => Self::USER_ID
        ]
    ],
    ...
];
```

Exemplo 4:
```php
private $columns = [
    ...
    'updatedBy' => [
        'default' => [
            'onUpdate' => Self::NOW
        ]
    ],
    ...
];
```

Exemplo 5:
```php
private $columns = [
    ...
    'id' => [
        'default' => [
            'sequence' => 'SEQ_SAMPLE_TABLE'
            'table' => 'SAMPLE_TABLE.CONTROL_COLUMN'
            self::MAX
        ]
    ],
    ...
];
```

### type
Tipo de dado da coluna.\
Tipo de dado: `string`\
Valor padrão: `'string'`

### format : `string`
Formato do campo na consulta

-- ### charset : `string`

### collation : `string`

### alias : `string`

### maxLength
Comprimento máximo, em caracteres, do valor campo.\
Tipo de dado: `int`\
Valor padrão: `0`

### minLength : `int`
Comprimento mínimo, em caracteres, do valor campo

### maxValue : `float|int`
Valor máximo que o campo pode receber

### minValue : `float|int`
Valor mínimo que o campo pode receber

### precision : `int`
### scale : `int`

### restrict : `array|string`
Lista de valores que o campo pode aceitar.
Exemplo 5:
```php
    ...
    'nome' => [
        'restrict' => [
            'alpha',
            'alphanumeric',
            'numeric',
        ],
    ...
```

### primary : `bool`
Indica se o campo compõe a chave primária da tabela.\
Tipo de dado: `bool`\
Valor padrão: `false`

### required
Indica se o campo é obrigatório. Pode ser usado em conjunto com default.\
Tipo de dado: `bool`\
Valor padrão: `false`

### transform : `string`
Modifica o valor da coluna antes do INSERT ou UPDATE.\
Tipo de dado: `array|string`\
Valor padrão: `null`
- UPPER\
Converte o valor para maiúsculas.
- LOWER\
Converte o valor para minúsculas.
- TRUNCATE\
Corta a string para caber na coluna
- TRIM\
Remove os espaços do início e do final da string
- NO_ACCENTS\
Remove acentos da string, conservando caracteres especiais

### unique : `string`
Nome da chave única que o campo faz parte

### unsigned : `bool`
Indica se o campo permite valores negativos
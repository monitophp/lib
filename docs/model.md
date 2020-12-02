# Model
A classe de model é uma representação da tabela do banco de dados

## Propriedades
### tableName
Nome da tabela

### keys
Array com as chave primárias da tabela

### fields
Array com os campos da tabela do modelo

#### Propriedades dos campos
- auto (bool)
  Indica se a coluna tem o valor preenchido automaticamente

- label (string)
  Rótulo do nome da coluna para exibição de mensagens

- name (string)
  Nome da coluna no banco de dados

- primary (bool)
  Indica se a coluna compõe a chave primária da tabela

- required (bool)
  Indica se o campo pode receber valores nulos

- default (string)
  Valor padrão para a coluna, caso seja informado nulo

- format (string)
  Formato do valor inserido/recuperado
  - O exemplo abaixo exibe o valor do campo número no formato configurado:
  ```
      'format' => 'Y-m-d H:i:s',
  ```

- maxLength (int)
  Tamanho máximo em caracteres que a coluna pode receber

- source (string)
  Usada em combinação com a propriedade `auto`, indica a origem do valor.
  Opções:
  - MAX
  - PARAM.TABLE_NAME/COLUMN_NAME
  - SEQUENCE.SEQUENCE_NAME
  - TABLE.TABLE_NAME/COLUMN_NAME
  Ganchos:
  - INSERT
  - UPDATE

- transform ()
  Transforma o valor no insert/update. As funções são executadas da direita para a esquerda
  No exemplo a função TRIM será executada em em seguida a funcção UPPER:
  ```
      'transform': 'UPPER|TRIM',
  ```

- type (string)
  Tipo de dado da coluna
  Opções:
    `int` para números inteiros
    `float` para números fracionários
    `string` para
    `date`
    `time`
    `datetime`
    `bool`
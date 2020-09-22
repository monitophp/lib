# Manipulação de dados

A manipulação de dados na MonitoLib se dá através das classes DAO - Data Access Object - de cada tabela.

## Tabela exemplo
``` sql
-- Tabela oracle de exemplo
CREATE TABLE pessoas (
  codigo NUMBER(10, 0) NOT NULL,
  nome VARCHAR2(50 BYTE) NOT NULL,
  idade NUMBER(3, 0),
  dtinc DATE NOT NULL,
  situacao CHAR(1 BYTE) DEFAULT 'A' NOT NULL,
  CONSTRAINT PESSOAS_PK PRIMARY KEY (CODIGO) ENABLE,
  CONSTRAINT PESSOAS_UK UNIQUE (NOME) ENABLE
);
```
Para cada tabela do banco de dados são necessários três arquivos para a manipulação dos dados: **dao**, **dto** e **model**

## Classe dao
A classe dao básica tem apenas a conexão com o banco de dados, herdado os métodos da classe dao do banco de dados:
```php
namespace Exemplo\Dao;

class Pessoa extends \MonitoLib\Database\Dao\Oracle
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2020-03-06
     * initial release
     */
    public function __construct()
    {
        \MonitoLib\Database\Connector::setConnectionName('winthor');
        parent::__construct();
    }
}
```
## Classe dto
A classe dto será apenas para transporte dos dados, tendo apenas gets e sets de cada propriedade da tabela
```php
    private $codigo;
    private $nome;
    private $idade;
    private $dtinc;
    private $situacao;

    ...
    public function getNome()
    {
        return $this->nome;
    }
    ...
    public function setNome($nome)
    {
        $this->nome = $nome;
        return $this;
    }
    ...
```

## Classe model
A classe model terá os atributos da tabela:
```php
namespace Exemplo\Model;

class Pessoa extends \MonitoLib\Database\Model
{
    const VERSION = '1.0.0';

    protected $tableName = 'pessoa';

    protected $fields = [
        'codigo' => [
            'type'      => 'int',
            'primary'   => true,
            'required'  => true,
        ],
        'nome' => [
            'maxLength' => 20,
            'required'  => true,
        ],
        ...
    ];

    protected $keys = ['codigo'];

    protected $constraints = [
        'unique' => [
            'PESSOAS_UK' => [
                'nome',
             ]
         ],
    ];
```
## Insert
Inserindo registros no banco de dados:
```php
// Instancia a classe dao da tabela
$pessoaDao = new \Exemplo\Dao\Pessoa;

// Cria um objeto dto vazio
$pessoaDto = new \Exemplo\Dto\Pessoa;

// Preenche o dto de acordo com cada propriedade
$pessoaDto->setNome('João da Silva');
$pessoaDto->setIdade(20);
$pessoaDto->setDtinc(App::now());

// Insere o registro no banco de dados
$pessoaDao->insert($pessoaDto);
```

## Select
A forma mais básica de buscar registros numa tabela é executando o método `list` da classes dao:
```php
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaList = $pessoaDao->list();
```

Para filtrar os resultados buscados no banco de dados a classe dao expando os méthodos da classe `Query`:
```php
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaList = $pessoaDao
    ->equal('id', 1)
    ->list();
```

## Update
A atualização de um registro é feita usando a classe dto:
```php
// Busca o registro no banco de dados
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDto = $pessoaDao
    ->equal('id', 1)
    ->get();
// Atualiza as informações
$pessoaDto->setIdade(25);
// Atualiza o registro no banco de dados
$pessoaDao->update($pessoaDto);
```

## Delete
Para deletar registros do banco de dados é possível passar um objeto dto, a chave primária ou filtrar os dados com `Query`:
```php
// Deleta um registro usando a chave primária da tabela com argumento
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDao->delete(1);

// Deleta um ou vários registros passando um array como argumento
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDao->delete([1, 2]);

// Deleta um registro passando um objeto dto como argumento
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDto = $pessoaDao
    ->equal('id', 1)
    ->get();
$pessoaDao->delete($pessoaDto);

// Deleta um ou vários registros usando filtro
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDao
    ->equal('id', 1)
    ->delete();
```

## Transações
Uma transação no banco de dados pode ser iniciada diretamente em uma classe dao:
```php
$pessoaDao = new \Exemplo\Dao\Pessoa;
$pessoaDao->beginTransaction();

$pessoaDao->commit();
$pessoaDao->rollback();
```
_A transação aberta é com o banco de dados da conexão e não apenas com a tabela do objeto dao_
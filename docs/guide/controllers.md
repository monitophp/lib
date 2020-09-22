# Controller

Um controller é a porta de entrada e saída de dados

Um classe básica de controller não tem métodos, herdando da classe <b>`\MonitoLib\Controller`</b>

```php
namespace Exemplo\Controller;

class Pessoa extends \MonitoLib\Controller
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2020-03-24
     * initial release
     */
}
```

A classe <b>`\MonitoLib\Controller`</b> oferece quatro métodos para manipulação dos dados:
## create
Insere um ou mais registros no banco de dados
## delete
Deleta um ou mais registros do banco de dados
## get
Obtém um ou mais registros do banco de dados
## update
Atualiza um ou mais registros do banco de dados
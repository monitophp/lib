# Rotas
Para executar um controle é necessário criar uma rota até um método público do controle

## Métodos
Cada rota tem um método http relacionado:
`delete, get, patch, post, put`

### parâmetros
$uri
Url relativa

$action
Controller e método do controller que será executado se a rota combinar

$secure
Define se a rota precisa de autenticação para ser acessada (ver [Classe App](./App]))

## Exemplo
```php {4}
use \MonitoLib\Router;

Router::get('pessoa/:{[0-9]+}', '\Exemplo\Controller\Pessoa@get', false);
Router::get('pessoa', '\Exemplo\Controller\Pessoa@list', false);
```

Fazendo uma requisição pelo navegador no endereço `http://localhost:8080` (a porta é só um exemplo) o resultado será o seguinte:
O método `get` da classe `Pessoa` será executado com a url `http://localhost:8080/pessoa/123`
O método `list` da classe `Pessoa` será executado com a url `http://localhost:8080/pessoa`

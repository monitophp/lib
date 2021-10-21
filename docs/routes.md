# Rotas

Para executar um método do controle é necessário criar uma rota até o método

$uri 


$action
Método do controller que será executado se a rota combinar

$secure
Define se a rota precisa de autenticação para ser acessada




public static function delete ($url, $action, $secure = true)
public static function get ($url, $action, $secure = true)
public static function patch ($url, $action, $secure = true)
public static function post ($url, $action, $secure = true)
public static function put ($url, $action, $secure = true)

```php {4}
use \MonitoLib\Router;

// Define a uri base da rota
Router::setBaseUri('/pessoa');

// Novas rotas não precisam informar a uri base
Router::get(':{[0-9]}', '\Exemplo\controller\Pessoa@get', false);
Router::get('', '\Exemplo\controller\Pessoa@list', false);
```

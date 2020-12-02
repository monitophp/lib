# Controller
Um controller é a porta de entrada e saída de dados de uma aplicação feita com `MonitoLib`

## Exemplo
### Rota
A rota abaixo tem quatro informações:
```php
Router::get('', '\App\Controller\Index@index', false);
```
### Controller
```php
<?php
namespace App\Controller;

class Index
{
    public function index()
    {
        return ['message' => 'Hello world'];
    }
}
```

Fazendo uma requisição pelo navegador no endereço `http://localhost:8080` (a porta é só um exemplo) o resultado será o seguinte:
```json
{
    "message": "Hello world"
}
```

## Parâmetros
É possível passar parâmetros para o controller pela rota. Cada parâmetro informado será um argumento do método de destino, por ordem de definição.



```php
namespace App\Controller;

use \MonitoLib\Request;

class Index extends \MonitoLib\Controller
{
    public function index($nome)
    {
        return ['message' => 'Hello ' . $nome];
    }
}
```
O parâmetro é definido como uma expressão regular no primeiro argumento da função de adicionar rota:
```php
Router::get(':{[0-9]}', '\App\Controller\Index@index', false);
```

Fazendo uma requisição pelo navegador no endereço `http://localhost:8080?nome=Monito` (a porta é só um exemplo) o resultado será o seguinte:
```json
{
    "message": "Hello Monito"
}
```

![Hello World](data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEYAAAAUCAAAAAAVAxSkAAABrUlEQVQ4y+3TPUvDQBgH8OdDOGa+oUMgk2MpdHIIgpSUiqC0OKirgxYX8QVFRQRpBRF8KShqLbgIYkUEteCgFVuqUEVxEIkvJFhae3m8S2KbSkcFBw9yHP88+eXucgH8kQZ/jSm4VDaIy9RKCpKac9NKgU4uEJNwhHhK3qvPBVO8rxRWmFXPF+NSM1KVMbwriAMwhDgVcrxeMZm85GR0PhvGJAAmyozJsbsxgNEir4iEjIK0SYqGd8sOR3rJAGN2BCEkOxhxMhpd8Mk0CXtZacxi1hr20mI/rzgnxayoidevcGuHXTC/q6QuYSMt1jC+gBIiMg12v2vb5NlklChiWnhmFZpwvxDGzuUzV8kOg+N8UUvNBp64vy9q3UN7gDXhwWLY2nMC3zRDibfsY7wjEkY79CdMZhrxSqqzxf4ZRPXwzWJirMicDa5KwiPeARygHXKNMQHEy3rMopDR20XNZGbJzUtrwDC/KshlLDWyqdmhxZzCsdYmf2fWZPoxCEDyfIvdtNQH0PRkH6Q51g8rFO3Qzxh2LbItcDCOpmuOsV7ntNaERe3v/lP/zO8yn4N+yNPrekmPAAAAAElFTkSuQmCC)

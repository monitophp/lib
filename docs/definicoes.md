# Introdução

::: danger ATENÇÃO
Esta documentação está incompleta
:::


## Instalação
A instação do `MonitoLib` é feita através do `composer`.
```sh
# Instala o pacote via composer
composer require monitophp/lib

# Copia o aplicativo de linha de comando para a raíz do projeto
cp vendor/monitophp/lib/files/mcl .

# Inicia a aplicação
php mcl lib:init
```

## Estrutura
Após iniciada a aplicação terá uma estrutura conforme abaixo:
<!-- textlint-disable terminology -->
``` text
.
├── config
│   ├── config.php
│   ├── init.php
├── routes
│   ├── default.php
├── src
│   ├── App
│   │   ├── Controller
│   │       ├── Index.php
├── vendor
│   ├── monitophp
│   │   ├── lib
│── .htaccess
│── index.php
│── mcl
└── composer.json
└── package.json
```
<!-- textlint-enable -->

## Model
### Buscando dados no banco de dados
```json
{
    "message": "hello world"
}
```
**Retorno:**
array ()




## Model
### Buscando dados no banco de dados
```php
// buscando um único registro
$classeDao = new \Dao\Classe();
$classeDto = $classeDao
    ->andEqual('campo', 'valor')
    ->get();

\MonitoLib\Dev::pre($classeDto);
```
**Retorno:**
array ()


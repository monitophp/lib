# MonitoLib
**DOCUMENTAÇÃO EM DESENVOLVIMENTO**

## Introdução

## Instalação
Para realizar a instalação do `MonitoLib` é necessário criar um arquivo `composer.json` na raíz do projeto e inserir as informações abaixo:
```sh
# Instala o pacote via composer
composer require monitophp/lib

# Copia o aplicativo de linha de comando para a raíz do projeto
cp ./vendor/monitophp/file/mcl .

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
│       ├── Controller
│           ├── Index.php
├── vendor
│   ├── monitophp
│       ├── lib
│           ├── ...
│── .htaccess
│── index.php
│── mcl
└── composer.json
└── package.json
```
<!-- textlint-enable -->

## Iniciando
Ao fazer uma requisição http do tipo get (por exemplo para `http://localhost:8080`) o resultado será o seguinte:
```json
{
    "message": "Hello world"
}
```

## Conteúdo
+ [Controles](./controller.md)
+ [Rotas](./route.md)
+ [Monito Command Line](./mcl.md)
+ [Banco de dados](./database.md)
+ [Desenvolvimento](./dev.md)
+ [Notas da versão](./release.md)

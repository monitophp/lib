# MonitoLib

## Introdução
A `MonitoLib` é um conjunto de bibliotecas para a criação de APIs restfull em PHP.

## Instalação
A instalação da `MonitoLib` é feita através do `composer`, conforme abaixo:
```sh
composer require monitophp/lib
```
Depois de concluída a instalação é necessário iniciar a aplicação, copiando o aplicativo de linha de comando `mcl` para a raiz do projeto e executando o comando a seguir:
```sh
cp vendor/src/monitophp/lib/files/mcl .

./mcl lib:install
```

## Tutoriais básicos
[Criar uma conexão com o banco de dados](./connection)\
[Consultar dados no banco](./oracle)\
[Criar uma rota](./routes)\
[Criar um comando](./commands)\
[Autenticando um usuário](./user)

## Referências da API
[Estrutura da aplicação](./structure)\
[Modelo de dados](./model)\
[Classe de aplicação](./model)
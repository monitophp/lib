# Monito Command Line
Aplicativo de linha de comando

## init
Inicia uma aplicação após a instação via composer

### --env
Ambiente da aplicação
> Padrão: dev

```bash
php mcl monito:init --env dev
```

## add-connection
Adiciona uma conexão com banco de dados ou endpoint REST

### --env
Ambiente de aplicação aplicação que usará a conexão
prod
> Padrão: o ambiente atualmente configurado na aplicação

### --type
Tipo de conexão: MySQL, Oracle ou Rest

### --host
Host

### --user

```bash
php mcl monito:add-connection nome_da_conexao --env prod --type Oracle --host //192.168.1.1 --user usuário_do_banco
```


```bash
php mcl lib:init
php mcl lib:add-connection
php mcl lib:set-env
php mcl lib:set-debug
```

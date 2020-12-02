# Monito Command Line
Aplicativo de linha de comando

## add-connection
Adiciona uma conexão com o banco de dados

### parâmetros
#### name
Nome da conexão com o banco de dados

### opções
#### --env
Ambiente da conexão
Default: prod

#### --type
Tipo de conexão

#### --host
Host do banco de dados da conexão

#### --user
Usuário do banco de dados da conexão

#### --pass
Senha do usuário do banco de dados

::: danger Atenção
Na configuração manual é possível ignorar a opção `--pass` e informar somente quando solicitado, evitando ficar registrada na linha de comando
:::

#### --db
Nome do banco de dados


## set-dev
## set-debug



## db ($index = 1)
public static function db ($index = 1)

```bash
php mcl monito:init
php mcl monito:add-connection
php mcl monito:set-env
php mcl monito:set-debug
```
## e
public static function e ($s, $breakLine = true)

## ee
public static function ee ($s = 'exited', $breakLine = true)

## lme
public static function lme ($class)

## pr
public static function pr ($a, $e = false, $index = 1)

## pre
public static function pre ($a)

## vd
public static function vd ($a, $depth = 0, $e = false, $index = 1)

## vde
public static function vde ($a, $depth = 0)

# Conexão com o banco de dados
Estática

## Parâmetro e opções
nome da conexão
--env
--type
--host
--user
--db

## Criando uma conexão
```sh
./mcl lib:add-connection name --env --type --host --user --db
```
A senha é informada a seguir:
```sh
Password: _
```
As conexões são armazenadas em uma array no arquivo `config\config.php`.
```php
$connections = [
    'tms' => [
        'dev' => [
            'type' => 'MySQL',
            'host' => '192.168.1.2:3306',
            'user' => 'db_user',
            'db'   => 'db_name',
            'pass' => '{encripted password string}',
        ],
    ],
];
```

## Editando uma conexão
Depois de criar uma conexão é possível editá-la, utilizando os mesmos parâmetros disponíveis na criação:
```sh
./mcl lib:upd-connection name --env --type --host --user --db --password
```

[Início](./readme)
# MonitoLib

## Introdução

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

## Estrutura
<!-- textlint-disable terminology -->
``` text
.
├── config/
│   ├── config.php
│   ├── init.php
├── routes/
│   ├── default.php
├── src/
├── vendor
│   ├── monitphp
│   │   ├── lib
│   ├── .vuepress _(**Optional**)_
│   │   ├── `components` _(**Optional**)_
│   │   ├── `theme` _(**Optional**)_
│   │   │   └── Layout.vue
│   │   ├── `public` _(**Optional**)_
│   │   ├── `styles` _(**Optional**)_
│   │   │   ├── index.styl
│   │   │   └── palette.styl
│   │   ├── `templates` _(**Optional, Danger Zone**)_
│   │   │   ├── dev.html
│   │   │   └── ssr.html
│   │   ├── `config.js` _(**Optional**)_
│   │   └── `enhanceApp.js` _(**Optional**)_
│   │
│   ├── README.md
│   ├── guide
│   │   └── README.md
│   └── config.md
│
│── .gitignore
│── .htaccess
│── index.php
│── mcl
└── composer.json
└── package.json
```

<!-- textlint-enable -->

# Directory Structure

VuePress follows the principle of **"Convention is better than configuration"**, the recommended document structure is as follows:

```txt
(array) seila {
    yss => akk
}
```

## Model
### Buscando dados no banco de dados
```php
// buscando um único registro
$classeDao = new \Dao\Classe;
$classeDto = $classeDao
    ->equal('campo', 'valor')
    ->get();

\MonitoLib\Dev::pre($classeDto);
```
**Retorno:**
```txt
array ()
```


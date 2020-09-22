# MonitoLib

## Introdução

## Instalação
Para realizar a instalação do `MonitoLib` é necessário criar um arquivo `composer.json` na raíz do projeto e inserir as informações abaixo:
```sh
composer require joelsonb/monitolib:dev-dev
```
Depois executa o comando abaixo:
```sh
php mcl lib:install
```

::: danger linha
:::

## Estrutura
<!-- textlint-disable terminology -->
``` text
.
├── config/
│   ├── init.php
├── routes/
├── src/
├── vendor
│   ├── joelsonb
│   │   ├── monitolib
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
$classeDao = new \dao\Classe;
$classeDto = $classeDao
    ->andEqual('campo', 'valor')
    ->get();

\MonitoLib\Dev::pre($classeDto);
```
**Retorno:**
array ()


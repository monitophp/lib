# Dev
Classe estática para uso em desenvolvimento

## db ($index = 1)
public static function db ($index = 1)

```php
\MonitoLib\Dev::db();
```
## e
Imprime o valor de uma string
public static function e ($s, $breakLine = true)

## ee
Imprime o valor de uma string e interrompe a execução do script
public static function ee ($s = 'exited', $breakLine = true)

## lme
public static function lme ($class)

## pr
public static function pr ($a, $e = false, $index = 1)

## pre
public static function pre ($a)

## vd
```php
\MonitoLib\Dev::vd($var);
```
public static function vd ($a, $depth = 0, $e = false, $index = 1)

## vde
Semelhante a `vd`, porém interrompe a execução do script.
public static function vde ($a, $depth = 0)
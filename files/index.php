<?php

define('MONITOLIB_ROOT_PATH', substr(__FILE__, 0, strrpos(str_replace('\\', '/', __FILE__), '/') + 1));
define('MONITOLIB_ROOT_URL', substr($_SERVER['PHP_SELF'], 0, strrpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/') + 1));

function ml_autoload($className)
{
    $dir = 'src' . DIRECTORY_SEPARATOR;

    if (substr($className, 0, 5) === 'cache') {
        $dir = 'tmp' . DIRECTORY_SEPARATOR;
    }

    $file = MONITOLIB_ROOT_PATH . DIRECTORY_SEPARATOR . $dir . str_replace('\\', '/', $className) . '.php';

    if (is_readable($file)) {
        require $file;
    }
}

spl_autoload_register('ml_autoload');

require MONITOLIB_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (is_cli()) {
    \MonitoLib\App::runCommand();
} else {
    \MonitoLib\App::run();
}

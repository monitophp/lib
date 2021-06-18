<?php
define('MONITOLIB_ROOT_PATH', substr(__FILE__, 0, (strrpos(str_replace('\\', '/', __FILE__), '/') + 1)));
define('MONITOLIB_ROOT_URL', substr($_SERVER['PHP_SELF'], 0, (strrpos(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/') + 1)));

// function to autoload classes
function MonitoAutoload($className)
{
    $dir = 'src' . DIRECTORY_SEPARATOR;

    if (substr($className, 0, 5) === 'cache') {
        $dir = '';
    }
    
    $file = MONITOLIB_ROOT_PATH . DIRECTORY_SEPARATOR . $dir . str_replace('\\', '/', $className) . '.php';

    if (is_readable($file)) {
        require $file;
    }
}

// Registers autoload function
spl_autoload_register('MonitoAutoload');

// Loads composer autoload class
require MONITOLIB_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

// Runs the application
\MonitoLib\App::run();

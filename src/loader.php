<?php
define('MONITOLIB_ROOT_PATH', str_replace('vendor/monitophp/lib/src/loader.php', '', __FILE__));
define('MONITOLIB_ROOT_URL', str_replace('index.php', '', $_SERVER['PHP_SELF']));

// Registers autoload function
spl_autoload_register(
    function($className) {
        $dir = 'src' . DIRECTORY_SEPARATOR;

        if (substr($className, 0, 5) === 'Cache') {
            $dir = 'tmp/';
        }

        $file = MONITOLIB_ROOT_PATH . $dir . str_replace('\\', '/', $className) . '.php';

        if (is_readable($file)) {
            require $file;
        }
    }
);

// Loads composer autoload class
require MONITOLIB_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
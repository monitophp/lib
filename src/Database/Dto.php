<?php
namespace MonitoLib\Database;

use \MonitoLib\App;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Dto
{
    const VERSION = '1.1.0';
    /**
     * 1.1.0 - 2018-07-27
     * new: static functions
     *
     * 1.0.0 - 2017-06-18
     * initial release
     */

    private static $crc;
    private static $keys;
    private static $object;

    private static function createDto ($className, $properties, $convertName)
    {
        $db = debug_backtrace();

        $output = "<?php\n"
            . "/**\n"
            . '* DTO class autogenerated at ' . date('c') . "\n"
            . '* created in ' . $db[3]['file'] . ', ' . $db[3]['line'] . "\n"
            . "*/\n"
            . "\n"
            . "namespace cache;\n"
            . "\n"
            . "class $className\n"
            . "{\n"
            ;

        $prp = '';
        $get = '';
        $set = '';

        foreach ($properties as $f) {
            $f = $convertName ? Functions::toLowerCamelCase(strtolower($f)) : strtolower($f);
            $g = 'get' . ucfirst($f);
            $s = 'set' . ucfirst($f);

            $prp .= "\tprivate \$$f;\n";

            $get .= "\t/**\n"
                . "\t* $g()\n"
                . "\t*\n"
                . "\t* @return \$$f\n"
                . "\t*/\n"
                . "\tpublic function $g ()\n"
                . "\t{\n"
                . "\t\treturn \$this->$f;\n"
                . "\t}\n"
                ;

            $set .= "\t/**\n"
                . "\t* $s()\n"
                . "\t*\n"
                . "\t* @return \$this\n"
                . "\t*/\n"
                . "\tpublic function $s (\$$f)\n"
                . "\t{\n"
                . "\t\t\$this->$f = \$$f;\n"
                . "\t\treturn \$this;\n"
                . "\t}\n"
                ;
        }

        $output .= $prp . $get . $set . "}";

        if (!@file_put_contents(App::getCachePath() . $className . '.php', $output)) {
            throw new InternalError('Erro ao gravar o cache!');
        }
    }
    public static function get ($array, $convertName)
    {
        if (!empty($array)) {
            $properties = isset($array[0]) && is_array($array[0]) ? array_keys($array[0]) : array_keys($array);

            // \MonitoLib\Dev::pr(serialize($properties));

            $className  = 'dto' . sha1(serialize($properties));

            if (!file_exists(App::getCachePath() . $className . '.php')) {
                self::createDto($className, $properties, $convertName);
            }

            $class = '\cache\\' . $className;
            return new $class;
        }
    }
}
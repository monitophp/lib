<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Response
{
    const VERSION = '1.0.0';

    private $module;
    private $command;
    private $params = [];
    private $options = [];

    static private $instance;

    private function __construct ()
    {

    }
    public static function getInstance ()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Response();
        }

        return self::$instance;
    }

    public function addModule($module)
    {
        $this->module = $module;
        return $this;
    }
    public function run()
    {
        $this->parse();

        $argv = $GLOBALS['argv'];
        // \MonitoLib\Dev::pre($argv);
        // exit;

        $argvCount = count($argv);

        if ($argvCount === 2 && $argv[1] === '-v') {
            $this->showHelp();
        }

        if (is_null($this->module)) {
            // Se nenhum módulo foi executado, lista todos os módulos disponíveis
            $files = glob(App::getConfigPath() . '*cli.php');

            if (!empty($files)) {
                foreach ($files as $filename) {
                    require_once $filename;
                }
            }

            $this->showModules();
        } else {
            $this->loadModule($this->module);
        }
    }
    private function loadMkr()
    {
        if (class_exists(\MonitoMkr\Cli\Mkr::class)) {
            // TODO: organizar require
            $filename = MONITOLIB_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'joelsonb/monitomkr/src/mkr.mcl.php';

            if (file_exists($filename)) {
                require_once $filename;
            } else {
                throw new \Exception('Arquivo não existe!');
            }
        } else {
            throw new \Exception('Package não existe!');
        }
    }
    private function loadModule($module)
    {
        switch ($module) {
            case 'mkr':
                $this->loadMkr();
                break;
            default:
        }

        $file     = App::getRoutesPath() . 'routes.php';
        $filename = App::getRoutesPath();

        foreach ($uriParts as $part) {
            $filename .= $part . '.';

            if (file_exists($filename . 'routes.php')) {
                $file = $filename . 'routes.php';
            } else {
                // Se o arquivo não existe, cancela a verificação e usa o último arquivo encontrado ou o base
                break;
            }
        }

        // \MonitoLib\Dev::ee($file);

        require_once $file;
    }
    private function parse()
    {
        $argv = $GLOBALS['argv'];

        // \MonitoLib\Dev::pr($argv);

        $mac = explode(':', $argv[1]);

        $this->module  = $mac[0] ?? null;
        $this->command = $mac[1] ?? null;

        $argv = array_slice($argv, 2);

        // \MonitoLib\Dev::pre($argv);

        $obj = null;
        $val = null;

        foreach ($argv as $arg) {
            if (preg_match('/^\-/', $arg)) {
                $obj = 'option';
                $val = trim($arg, '-');
                $this->options[$val] = true;
            } else {
                if ($obj === 'option') {
                    $this->options[$val] = trim($arg, '\\');
                    $obj = 'argument';
                } else {
                    $this->params[] = $arg;
                    $obj = 'param';
                }
            }
        }

        // \MonitoLib\Dev::pre($this);
    }
    private function showHelp()
    {
        echo 'Monito Command Line v' . self::VERSION . "\n";
        exit;
    }
    /**
    * Exibe os módulos disponíveis ou a ajuda, caso na haja módulos
    */
    private function showModules()
    {

    }
}
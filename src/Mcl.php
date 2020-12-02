<?php
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Mcl
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2020-09-28
    * fix: loadModule()
    */

    private static $instance;
    private $module;
    private $command;

    private function __construct ()
    {

    }
    public static function getInstance ()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Mcl();
        }

        return self::$instance;
    }
    public function addModule($module)
    {
        $this->module = $module;
    }
    public function run()
    {
        $request = $this->parse();
        $module  = $request->getModule();
        $command = $request->getCommand();

        // Requires an app init file, if exists
        if (file_exists($init = App::getConfigPath() . 'init.php')) {
            require $init;
        }

        // Carrega o módulo
        switch ($module) {
            case 'lib':
                $this->module = new \MonitoLib\Mcl\Command\Lib();
                break;
            case 'mkr':
                $this->module = new \MonitoMkr\Command\Mkr();
                break;
            default:
                $this->loadModule($module);
        }

        // Configura o módulo
        $this->module->setup();

        if (!is_null($command)) {
            $command = $this->module->getCommand($command);
            // Verifica os parâmetros
            $params  = $command->getParams();

            if (!empty($params)) {
                $i = 0;

                foreach ($params as $param) {
                    $value = $request->getParams()[$i] ?? null;
                    $name = $param->getName();

                    if ($param->getRequired() && is_null($value)) {
                        throw new BadRequest("O parâmetro $name é obrigatório!");
                    }

                    $param->setValue($value);

                    $i++;
                }
            }

            // Verifica as opções e argumentos
            $options = $command->getOptions();

            if (!empty($options)) {
                foreach ($options as $option) {
                    $alias = $option->getAlias();
                    $name  = $option->getName();
                    $value = $request->getOption($name) ?? $request->getOption($alias);

                    if ($option->getRequired() && is_null($value)) {
                        throw new BadRequest("A opção $name é obrigatória!");
                    }

                    $option->setValue($value);
                }
            }

            $request = \MonitoLib\Mcl\Request::getInstance();
            $request->setModule($module)
                ->setCommand($command)
                ->setParams($params)
                ->setOptions($options);

            // Executa o comando
            $className = $command->getClass();
            $method    = $command->getMethod();

            $class = new $className();
            $class->$method();
        }

        if ($request->getOption('help')) {
            if (is_null($command)) {
                $this->showModuleHelp();
            } else {
                $this->showCommandHelp();
            }
        }

        if ($request->getOption('version')) {
            if (is_null($command)) {
                $this->module->showVersion();
            } else {
                $this->showVersion();
            }
        }
    }
    private function loadCommand($command)
    {
        $command = $this->module->getCommand($command);

        $className   = $command->getClass();
        $classObject = new $className();
        $method      = $command->getMethod();

        $classObject->$method();

        \MonitoLib\Dev::vde($classObject);

        \MonitoLib\Dev::pre($command);
    }
    private function loadModule($module)
    {
        $file = App::getRoutesPath() . 'cli.php';

        if (!file_exists($file)) {
            throw new Exception('Não há arquivos de comandos!');
        }

        require_once $file;

        if (!isset($commands[$module])) {
            throw new Exception('Módulo não existe!');
        }

        $this->module = new $commands[$module]();
    }
    private function parse()
    {
        $argv    = $GLOBALS['argv'];
        $module  = 'mcl';
        $command = null;
        $params  = [];
        $options = [];
        $index   = 1;

        // Verifica se foi informado um módulo
        if (isset($argv[1]) && preg_match('/^[A-Za-z]/', $argv[1], $m)) {
            $mac    = explode(':', $argv[1]);
            $module = $mac[0] ?? null;
            // \MonitoLib\Dev::pre($m);

            if (isset($mac[1])) {
                $command = $mac[1] ?? null;
            }

            $index = 2;
        }

        $argv = array_slice($argv, $index);

        $obj = null;
        $val = null;

        foreach ($argv as $arg) {
            if (preg_match('/^\-/', $arg)) {
                $obj = 'option';
                $val = trim($arg, '-');
                $options[$val] = true;
            } else {
                if ($obj === 'option') {
                    $options[$val] = trim($arg, '\\');
                    $obj = 'argument';
                } else {
                    $params[] = $arg;
                    $obj = 'param';
                }
            }
        }

        $request = \MonitoLib\Mcl\Request::getInstance();
        $request->setModule($module)
            ->setCommand($command)
            ->setParams($params)
            ->setOptions($options);

        return $request;
    }
    private function showCommandHelp()
    {
        echo 'list command options' . "\n";
    }
    private function showModuleHelp()
    {
        echo $this->module->getHelp() . "\n";
        // echo 'Ajuda da Monito Command Line v' . $this->VERSION . "\n";
        $commands = $this->module->listCommands();

        foreach ($commands as $cn => $ch) {
            echo $cn . ' ' . $ch . "\n";
        }

        echo "<todo>\n";
        exit;
    }
    private function showVersion()
    {
        echo 'Monito Command Line v' . self::VERSION . "\n";
        exit;
    }
    /**
    * Exibe os módulos disponíveis ou a ajuda, caso na haja módulos
    */
    private function showModules()
    {
        if (is_null($this->module)) {
            // Se nenhum módulo foi executado, lista todos os módulos disponíveis
            $files = glob(App::getConfigPath() . '*cli.php');

            if (!empty($files)) {
                foreach ($files as $filename) {
                    require_once $filename;
                }
            }

            $this->showModules();
        }
    }
}
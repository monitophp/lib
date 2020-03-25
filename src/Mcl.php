<?php
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Mcl
{
    const VERSION = '1.0.0';

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
        // \MonitoLib\Dev::pre($this->module);

        $request = $this->parse();
        $module  = $request->getModule();
        $command = $request->getCommand();

        // Carrega o módulo
        switch ($module) {
            case 'mcl':
                $this->loadMcl();
                $this->module = new \MonitoLib\Command\Mcl();
                break;
            case 'mkr':
                // $this->loadMkr();
                $this->module = new \MonitoMkr\Command\Mkr();
                break;
            default:
                $this->loadModule($module);
        }

        // Configura o módulo
        $this->module->setup();

        // Verifica os parâmetros
        $command = $this->module->getCommand($command);
        $params  = $command->getParams();

        if (!empty($params)) {
            $i = 0;

            // \MonitoLib\Dev::pre($request);

            foreach ($params as $param) {
                $value = $request->getParams()[$i] ?? null;
                $name = $param->getName();

                // \MonitoLib\Dev::pre($value);

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
                // $value = $request->getParams()[$i] ?? null;

                $value = $request->getOption($name) ?? $request->getOption($alias);

                // \MonitoLib\Dev::vde($value);

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

        // \MonitoLib\Dev::pre($request);

        // Executa o comando
        $className = $command->getClass();
        $method    = $command->getMethod();

        $class = new $className();
        $class->$method();

        // \MonitoLib\Dev::pre($command->getOptions());

        if (!is_null($command)) {
            \MonitoLib\Dev::ee('aqui nao');
            $this->loadCommand($command);
        }

        if ($request->getOption('help')) {
            if (is_null($command)) {
                $this->showModuleHelp();
            } else {
                $this->showCommandHelp();
            }
        }

        if ($request->getOption('version')) {
            $this->showVersion();
        }
    }
    private function loadMcl()
    {
        \MonitoLib\Dev::ee('ok');
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

        // $this->showHelp();

        // \MonitoLib\Dev::pre($this->$module->getHelp());

        // \MonitoLib\Dev::vde($this->$module);
        // \MonitoLib\Dev::pre($filename);
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


        // $file     = App::getRoutesPath() . 'routes.php';
        // $filename = App::getRoutesPath();

        // foreach ($uriParts as $part) {
        //     $filename .= $part . '.';

        //     if (file_exists($filename . 'routes.php')) {
        //         $file = $filename . 'routes.php';
        //     } else {
        //         // Se o arquivo não existe, cancela a verificação e usa o último arquivo encontrado ou o base
        //         break;
        //     }
        // }

        // \MonitoLib\Dev::ee($file);

        // require_once $file;
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
        echo 'Monito Command Line v' . $this->VERSION . "\n";
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
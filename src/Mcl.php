<?php
namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Mcl\Request;

class Mcl
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2020-09-28
    * fix: loadModule()
    */

    private static $module;

    public static function addModule($module)
    {
        self::$module = $module;
    }
    public static function run()
    {
        try {
            // Parses command line input
            self::parse();

            // $module      = Request::getModule();
            $command = Request::getCommand();

            // Config file
            if (file_exists($config = App::getConfigPath() . 'config.php')) {
                require $config;
            }

            // Requires an app init file, if exists
            if (file_exists($init = App::getConfigPath() . 'init.php')) {
                require $init;
            }

            // $params = Request::getParams();

            // \MonitoLib\Dev::pre($params);



            // // Configura o módulo
            // self::$module->setup();

            // if (!is_null($commandName)) {
                // $command = self::$module->getCommand($commandName);

            //     if (is_null($command)) {
            //         throw new NotFound("Command \033[31m{$commandName}\033[0m not found in module");
            //     }

            //     // Verifica os parâmetros do comando
            //     $params = $command->getParams();

            //     if (!empty($params)) {
            //         $i = 0;

            //         foreach ($params as $param) {
            //             $value = Request::getParams()[$i] ?? null;
            //             $name = $param->getName();

            //             if ($param->getRequired() && is_null($value)) {
            //                 throw new BadRequest("O parâmetro $name é obrigatório!");
            //             }

            //             $param->setValue($value);
            //             $i++;

            //             // Adiciona o parâmetro na requisição
            //             Request::addParam($name, $param);
            //         }
            //     }

            //     // Verifica as opções e argumentos
            //     $options = $command->getOptions();

            //     if (!empty($options)) {
            //         foreach ($options as $option) {
            //             $alias = $option->getAlias();
            //             $name  = $option->getName();

            //             try {
            //                 $value = Request::getOption($name) ?? Request::getOption($alias);
            //             } catch (NotFound $e) {
            //                 $value = null;
            //             }

            //             if ($option->getRequired() && is_null($value)) {
            //                 throw new BadRequest("A opção $name é obrigatória!");
            //             }

            //             $option->setValue($value);
            //             Request::addOption($name, $options);
            //         }
            //     }

            //     // Request::setModule($module);
            //     // Request::setCommand($command);

                // Executa o comando
                $className = $command->getClass();
                $method    = $command->getMethod();

                $class = new $className();
                $class->$method();
            // }

            // if (Request::getOption('help')) {
            //     if (is_null($command)) {
            //         self::showModuleHelp();
            //     } else {
            //         self::showCommandHelp();
            //     }
            // }

            // if (Request::getOption('version')) {
            //     if (is_null($command)) {
            //         self::$module->showVersion();
            //     } else {
            //         self::showVersion();
            //     }
            // }
        } catch (\Throwable $e) {
            \MonitoLib\Dev::pre($e);
        }
    }
    private static function loadCommand($command)
    {
        $command = self::$module->getCommand($command);

        $className   = $command->getClass();
        $classObject = new $className();
        $method      = $command->getMethod();

        $classObject->$method();

        \MonitoLib\Dev::vde($classObject);

        \MonitoLib\Dev::pre($command);
    }
    private static function loadModule(string $module) : void
    {
        $file = App::getRoutesPath() . 'mcl.php';

        if (!file_exists($file)) {
            throw new \Exception('Não há arquivos de rota para comandos');
        }

        require_once $file;

        if (!isset($commands[$module])) {
            throw new \Exception("Módulo não $module existe");
        }

        self::$module = new $commands[$module]();
    }
    private static function parse()
    {
        $argv        = $GLOBALS['argv'];
        $moduleName  = null;
        $commandName = null;
        $index       = 1;

        // Identifica o módulo e o comando
        if (isset($argv[1]) && preg_match('/^[A-Za-z]/', $argv[1], $m)) {
            $mac    = explode(':', $argv[1]);
            $moduleName = $mac[0] ?? null;

            if (isset($mac[1])) {
                $commandName = $mac[1] ?? null;
            }

            $index = 2;
        }

        if (is_null($moduleName)) {
            self::showHelp();
            exit;
        }

        // Carrega o módulo
        switch ($moduleName) {
            case 'lib':
                self::$module = new \MonitoLib\Mcl\Command\Lib();
                break;
            case 'mkr':
                self::$module = new \MonitoMkr\Command\Mkr();
                break;
            default:
                self::loadModule($moduleName);
        }

        // Configura o módulo
        self::$module->setup();

        // Se não foi informado um comando, exibe a ajuda do módulo
        if (is_null($commandName)) {
            self::$module->showHelp();
            exit;
        }

        $command = self::$module->getCommand($commandName);

        if (is_null($command)) {
            throw new NotFound("Command \033[31m{$commandName}\033[0m not found in module");
        }

        $obj = null;
        $val = null;

        $params = [];
        $options = [];

        $argv = array_slice($argv, $index);

        // Analisa os parâmetros e opções passados na linha de comando
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

        // \MonitoLib\Dev::pr($params);
        // \MonitoLib\Dev::pr($options);

        $availableParams  = $command->getParams();
        $availableOptions = $command->getOptions();

        if (!empty($availableParams)) {
            Request::setParams($availableParams);

            $i = 0;
            foreach ($availableParams as $p) {
                $name = $p->getName();
                $p->setValue($params[$i] ?? null);
                Request::addParam($name, $p);
                $i++;
            }
        }

        // \MonitoLib\Dev::vde(Request::getParams());

        if (!empty($availableOptions)) {
            Request::setOptions($availableOptions);

            foreach ($availableOptions as $p) {
                $name  = $p->getName();
                $alias = $p->getAlias();
                $p->setValue($options[$name] ?? $options[$alias] ?? null);
                Request::addOption($name, $p);
            }
        }

        // \MonitoLib\Dev::pre(Request::getOptions());










        //     // Verifica os parâmetros do comando
        //     $params = $command->getParams();

        //     if (!empty($params)) {
        //         $i = 0;

        //         foreach ($params as $param) {
        //             $value = Request::getParams()[$i] ?? null;
        //             $name = $param->getName();

        //             if ($param->getRequired() && is_null($value)) {
        //                 throw new BadRequest("O parâmetro $name é obrigatório!");
        //             }

        //             $param->setValue($value);
        //             $i++;

        //             // Adiciona o parâmetro na requisição
        //             Request::addParam($name, $param);
        //         }
        //     }






        // // Envia as informações analisadas para a requisição
        // Request::setModule($module);
        Request::setCommand($command);
        // Request::setOptions($options);
    }
    private static function showCommandHelp()
    {
        echo 'list command options' . "\n";
    }
    private static function showModuleHelp()
    {
        echo self::$module->getHelp() . "\n";
        // echo 'Ajuda da Monito Command Line v' . self::$VERSION . "\n";
        $commands = self::$module->listCommands();

        foreach ($commands as $cn => $ch) {
            echo $cn . ' ' . $ch . "\n";
        }

        echo "<todo>\n";
        exit;
    }
    private static function showVersion()
    {
        echo 'MonitoLib Command Line v' . self::VERSION . "\n";
        exit;
    }
    /**
    * Exibe os módulos disponíveis ou a ajuda, caso na haja módulos
    */
    private static function showModules()
    {
        if (is_null(self::$module)) {
            // Se nenhum módulo foi executado, lista todos os módulos disponíveis
            $files = glob(App::getConfigPath() . '*cli.php');

            if (!empty($files)) {
                foreach ($files as $filename) {
                    require_once $filename;
                }
            }

            self::showModules();
        }
    }
}
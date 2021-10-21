<?php
namespace MonitoLib\Mcl\Cli;

ini_set('max_execution_time', '0');
ini_set('memory_limit','4096M');

use \MonitoLib\App;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;

class Lib extends \MonitoLib\Mcl\Controller
{
    public function init()
    {
        // Copia .gitignore
        copy(source, MONITOLIB_ROOT_PATH);

        // Copia .htaccess
        copy(source, MONITOLIB_ROOT_PATH);

        // Copia index.php
        copy(source, MONITOLIB_ROOT_PATH);

        // Cria config/init.php
        $this->createInit();
    }
    public function install()
    {
        // $resposta = $this->question("Iniciar a instalacao da aplicacao?");

        // if (!$resposta) {
        //     throw new \MonitoLib\Exception\NoError();
        // }

        // Cria o arquivo de inicialização
        $this->createInit();

        // Cria o htaccess
        $this->createHtaccess();

        // Cria gitignore
        $this->gitignore();

        // Cria index
        $this->createIndex();


        // while (!in_array($resposta, [1, 2])) {
        //     echo "kual db?\n";
        //     echo "1 - misiquele\n";
        //     echo "2 - oracu\n";
        //     $resposta = readline("informe_o_numero_do_db_selecionado:_");

        //     if (!in_array($resposta, [1, 2])) {
        //         echo "resposta invalida!\n";
        //     }
        // }

        // echo substr(json_encode('Sônú'), 1, -1) . "\n";

        // readline_callback_handler_install($prompt, function() {});
        // $char = stream_get_contents(STDIN, 1);
        // readline_callback_handler_remove();
        // echo $char . "\n";

        // echo "\033[m" . "tudo\n";



        // // \MonitoLib\Dev::pre(readline_info());

        // echo "Cânçádú? \n";
        // $xyz = readline('rizposta: ');
        // echo "rizutadu: $xyz\n";
        // // readline_redisplay();

        // $senha = null;

        // $z = true;
        // while ($z) {
        //     readline_callback_handler_install('senha: ', function() {});
        //     $x = stream_get_contents(STDIN, 1);
        //     readline_callback_handler_remove();

        //     if (ord($x) === 10) {
        //         $z = false;
        //         break;
        //     }

        //     $senha .= $x;

        //     echo " \r";
        // }

        // \MonitoLib\Dev::vde($senha);

        // $resposta = $this->question("Qual s a pergunta?");
        // \MonitoLib\Dev::ee($resposta . "\n");
        echo "done\n";
    }
    private function createHtaccess()
    {
        $script = <<<FILE
<IfModule mod_php5.c>\n
    php_value max_execution_time 18000\n
</IfModule>\n
\n
Options FollowSymLinks\n
RewriteEngine On\n
\n
# Scripts PHP\n
RewriteCond %{REQUEST_FILENAME} !-f\n
RewriteRule (.*) index.php?route=$1 [PT,QSA]\n
RewriteCond %{HTTP:Authorization} ^(.*)\n
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]\n
FILE;
        return $script;
    }
    private function gitignore() : string
    {
        $script = <<<FILE
cache\n
config/config.php\n
log\n
sqlnet.log\n
storage\n
tmp\n
vendor\n
debug.log
FILE;
        return $script;
    }
    private function createInit()
    {
        // TODO: permitir mudar valores
        $script = <<<PHP
<?php\n
ini_set('display_errors', 1);\n
ini_set('display_startup_errors', 1);\n
error_reporting(E_ALL);\n
/**\n
 * App default timezone\n
 */\n
date_default_timezone_set('America/Bahia');\n
\n
header('Access-Control-Allow-Origin: *');\n
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Authorization');\n
header('Access-Control-Allow-Methods: GET,PATCH,POST,PUT,DELETE,OPTIONS');\n
// \MonitoLib\App::setEnv(2);\n
\MonitoLib\App::setDebug(2);\n
\n
// Autentica o usuário\n
// \App\Controller\User::auth();
PHP;
        return $script;
    }
    private function createIndex()
    {
        // TODO: permitir mudar valores
        $script = <<<PHP
<?php\n
define('MONITOLIB_ROOT_PATH', substr(__FILE__, 0, (strrpos(str_replace('\\\', '/', __FILE__), '/') + 1)));\n
define('MONITOLIB_ROOT_URL', substr(\$_SERVER['PHP_SELF'], 0, (strrpos(str_replace('\\\', '/', \$_SERVER['PHP_SELF']), '/') + 1)));\n
\n
// function to autoload classes\n
function MonitoAutoload (\$className)\n
{\n
    \$dir = 'src' . DIRECTORY_SEPARATOR;\n
\n
    if (substr(\$className, 0, 5) === 'cache') {\n
        \$dir = '';\n
    }\n
\n
    \$file = MONITOLIB_ROOT_PATH . DIRECTORY_SEPARATOR . \$dir . str_replace('\\\', '/', \$className) . '.php';\n
\n
    if (is_readable(\$file)) {\n
        require \$file;\n
    }\n
}\n
\n
// Registers autoload function\n
spl_autoload_register('MonitoAutoload');\n
\n
// Loads composer autoload class\n
require MONITOLIB_ROOT_PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';\n
\n
// Runs the application\n
\MonitoLib\App::run();
PHP;
        return $script;
    }
}

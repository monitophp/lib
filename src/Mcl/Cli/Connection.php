<?php
namespace MonitoLib\Mcl\Cli;

use \MonitoLib\App;
use \MonitoLib\Config;
use \MonitoLib\Database\Connector;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Mcl\Request;

class Connection extends \MonitoLib\Mcl\Controller
{
    public function add()
    {
        $name = Request::getParam('name')->getValue();
        $env  = Request::getOption('env')->getValue() ?? 'prod';
        $type = Request::getOption('type')->getValue();
        $host = Request::getOption('host')->getValue();
        $user = Request::getOption('user')->getValue();
        $db   = Request::getOption('db')->getValue();

        if (is_null($name)) {
            $name = $this->question('Informe o nome da conexao: ');
        }

        if (is_null($env)) {
            $env = $this->question('Informe o ambiente da conexao: ');
        }

        if (is_null($type)) {
            $type = $this->choice(
                'Indique o tipo da conexao: ',
                [
                    1 => 'MySQL',
                    2 => 'Oracle',
                    3 => 'Rest',
                    4 => 'MongoDB',
                ]
            );
        }

        $pass = $this->input('Senha: ');

        $connections = Connector::getConnectionsList();

        // \MonitoLib\Dev::pre($connections);

        $config = [];

        if (!is_null($type)) {
            $config['type'] = $type;
        }
        if (!is_null($host)) {
            $config['host'] = $host;
        }
        if (!is_null($user)) {
            $config['user'] = $user;
        }
        if (!is_null($db)) {
            $config['database'] = $db;
        }
        if (!is_null($pass)) {
            $pass = Functions::encrypt($pass, $name . $env);
            $config['pass'] = $pass;
        }

        $connections[$name][$env] = $config;

        Connector::setConnections($connections);

        Config::update();
    }
}

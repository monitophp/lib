<?php
namespace MonitoLib\Mcl\Cli;

use \MonitoLib\App;
use \MonitoLib\Config;
use \MonitoLib\Database\Connector;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;

class Connection extends \MonitoLib\Mcl\Controller
{
    public function add()
    {
        $name = $this->request->getParam('name')->getValue();
        $env  = $this->request->getOption('env')->getValue() ?? 'prod';
        $type = $this->request->getOption('type')->getValue();
        $host = $this->request->getOption('host')->getValue();
        $user = $this->request->getOption('user')->getValue();
        $db   = $this->request->getOption('db')->getValue();

        if (is_null($name)) {
            $name = $this->question('Informe o nome da conexao: ');
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

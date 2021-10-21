<?php
namespace MonitoLib\Mcl\Cli;

use \MonitoLib\App;
use \MonitoLib\Functions;
use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;

class Application extends \MonitoLib\Mcl\Controller
{
    public function generateSalt()
    {
        // Gera uma string aleatória para ser usada como salt
        Config::setSalt($salt);

        // Atualiza o arquivo de configuração
        Config::update();
    }
    public function setDebug()
    {
        $debug = $this->request->getParam('debug')->getValue();

        // Define o nível de debug
        Config::setDebug($debug);

        // Atualiza o arquivo de configuração
        Config::update();
    }
    public function setEnv()
    {
        $env = $this->request->getParam('env')->getValue();

        // Define o environment
        Config::setEnv($env);

        // Atualiza o arquivo de configuração
        Config::update();
    }
    public function setTimezone()
    {
        $timezone = $this->request->getParam('timezone')->getValue();

        // Define o timezone
        Config::setTimezone($timezone);

        // Atualiza o arquivo de configuração
        Config::update();   
    }
}

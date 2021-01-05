<?php
namespace MonitoLib\Mcl\Command;

use \MonitoLib\Mcl\Command;
use \MonitoLib\Mcl\Module;
use \MonitoLib\Mcl\Option;
use \MonitoLib\Mcl\Param;

class Lib extends Module
{
    const VERSION = '1.0.0';

    protected $name = 'lib';
    protected $help = 'Configura a aplicação';

    public function setup()
    {
        // Adiciona um comando
        $command = $this->addCommand(
            new class extends Command
            {
                protected $name   = 'add-connection';
                protected $class  = '\MonitoLib\Mcl\Cli\Connection';
                protected $method = 'add';
                protected $help   = 'Adiciona uma conexão';
            }
        );
        // Adiciona um parâmetro ao comando
        $command->addParam(
            new class extends Param
            {
                protected $name     = 'name';
                protected $help     = 'Nome da conexão com o banco de dados';
                protected $required = true;
            }
        );
        // Adiciona uma opção ao comando
        $command->addOption(
            new class extends Option
            {
                protected $name     = 'env';
                protected $alias    = '';
                protected $help     = 'Ambiente da conexão. Default: prod';
                protected $required = true;
                protected $type     = 'string';
            }
        );
        // Adiciona uma opção ao comando
        $command->addOption(
            new class extends Option
            {
                protected $name     = 'type';
                protected $alias    = '';
                protected $help     = 'Tipo de conexão';
                protected $required = true;
                protected $type     = 'string';
            }
        );
        // Adiciona uma opção ao comando
        $command->addOption(
            new class extends Option
            {
                protected $name     = 'host';
                protected $alias    = '';
                protected $help     = 'Host do banco de dados da conexão';
                protected $required = true;
                protected $type     = 'string';
            }
        );
        // Adiciona uma opção ao comando
        $command->addOption(
            new class extends Option
            {
                protected $name     = 'user';
                protected $alias    = '';
                protected $help     = 'Usuário do banco de dados da conexão';
                // protected $required = true;
                protected $type     = 'string';
            }
        );
        // Adiciona uma opção ao comando
        $command->addOption(
            new class extends Option
            {
                protected $name     = 'db';
                protected $alias    = '';
                protected $help     = 'Nome do banco de dados';
                // protected $required = true;
                protected $type     = 'string';
            }
        );
    }
}
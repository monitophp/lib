<?php
namespace MonitoLib\Mcl\Command;

class Lib extends \MonitoLib\Mcl\Module
{
    const VERSION = '1.0.0';

    protected $name = 'lib';
    protected $help = 'Configura a aplicação';

    public function setup()
    {
        // Adiciona uma conexão na aplicação
        $this->addCommand(new Lib\AddConnection());

        // Inicializa uma nova aplicação
        // Modifica o ambiente da aplicação
        // Reseta uma aplicação
        // Atualiza uma aplicação (??)
        // Define fuso horário
        // Limpar cache
    }
}
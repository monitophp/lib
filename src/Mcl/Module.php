<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Module // extends \MonitoLib\Mcl
{
    const VERSION = '1.0.0';

    protected $commands = [];
    protected $name;
    protected $help;

    public function addCommand(\MonitoLib\Mcl\Command $command)
    {
        $name = $command->getName();
        $this->commands[$name] = $command;
        return $command;
    }
    public function getCommand(string $command)
    {
        return $this->commands[$command] ?? null;
    }
    public function getHelp()
    {
        return $this->help;
    }
    public function listCommands()
    {
        $commands = [];

        foreach ($this->commands as $command) {
            $commands[$command->getName()] = $command->getHelp();
        }

        return $commands;
    }
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function showVersion()
    {
        echo $this->name . ' v' . self::VERSION . "\n";
    }
}
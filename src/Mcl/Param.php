<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Param // extends \MonitoLib\Mcl
{
    protected string $help = '';
    protected string $name = '';
    protected $required;
    protected ?string $value;

    public function addCommand(\MonitoLib\Mcl\Command $command)
    {
        $name = $command->getName();
        $this->commands[$name] = $command;
    }
        /**
    * getName
    *
    * @return $name
    */
    public function getName()
    {
        return $this->name;
    }
    public function getRequired()
    {
        return $this->required;
    }
    public function getValue()
    {
        return $this->value;
    }
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
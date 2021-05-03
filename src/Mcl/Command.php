<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Functions;

class Command // extends \MonitoLib\Mcl
{
    protected $class;
    protected $help;
    protected $method;
    protected $name;
    protected $options = [];
    protected $params = [];

    public function addOption(\MonitoLib\Mcl\Option $option)
    {
        $name = $option->getName();
        $this->options[$name] = $option;
    }
    public function addParam(\MonitoLib\Mcl\Param $param)
    {
        $name = $param->getName();
        $this->params[$name] = $param;
    }
    /**
    * getClass
    *
    * @return $class
    */
    public function getClass()
    {
        return $this->class;
    }
    /**
    * getHelp
    *
    * @return $help
    */
    public function getHelp()
    {
        return $this->help;
    }
    /**
    * getMethod
    *
    * @return $method
    */
    public function getMethod()
    {
        return $this->method;
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
    /**
    * getOptions
    *
    * @return $options
    */
    public function getOptions()
    {
        return $this->options;
    }
    /**
    * getParams
    *
    * @return $params
    */
    public function getParams()
    {
        return $this->params;
    }
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }
    public function setMethod($method)
    {
        $p = explode('@', $method);

        if (count($p) > 1) {
            $this->class = $p[0];
            $this->method = $p[1];
        } else {
            $this->method = $p[0];
        }

        return $this;
    }
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
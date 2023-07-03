<?php
namespace MonitoLib\Mcl;

class Option
{
    protected $alias;
    protected $help;
    protected $method;
    protected $name;
    protected $required;
    protected $type;
    protected $value;

    /**
    * getAlias
    *
    * @return $alias
    */
    public function getAlias()
    {
        return $this->alias;
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
    * getRequired
    *
    * @return $required
    */
    public function getRequired()
    {
        return $this->required;
    }
    /**
    * getType
    *
    * @return $type
    */
    public function getType()
    {
        return $this->type;
    }
    /**
    * getValue
    *
    * @return $value
    */
    public function getValue()
    {
        return $this->value;
    }
    /**
     * setAlias
     *
     * @param $alias
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    /**
     * setHelp
     *
     * @param $help
     */
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }
    /**
     * setMethod
     *
     * @param $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }
    /**
     * setName
     *
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
    /**
     * setRequired
     *
     * @param $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
        return $this;
    }
    /**
     * setType
     *
     * @param $type
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /**
     * setValue
     *
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
}
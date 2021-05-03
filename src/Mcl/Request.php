<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\InternalError;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Functions;

class Request
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0
    */

    private $module;
    private $command;
    private $params = [];
    private $options = [];

    static private $instance;

    private function __construct()
    {

    }
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Request();
        }

        return self::$instance;
    }
    /**
    * getModule
    *
    * @return $module
    */
    public function getModule()
    {
        return $this->module;
    }
    /**
    * getCommand
    *
    * @return $command
    */
    public function getCommand()
    {
        return $this->command;
    }
    /**
    * getParam
    *
    * @return $param
    */
    public function getParam($param)
    {
        return $this->params[$param] ?? null;
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
    /**
    * getOption
    *
    * @return $option
    */
    public function getOption($option)
    {
        return $this->options[$option] ?? null;
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
     * setModule
     *
     * @param $module
     */
    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }
    /**
     * setCommand
     *
     * @param $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
        return $this;
    }
    /**
     * setParams
     *
     * @param $params
     */
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }
    /**
     * setOptions
     *
     * @param $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }
}
<?php
namespace MonitoLib\Mcl;

use \MonitoLib\Exception\NotFound;

class Request
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2021-06-30
    * new: static methods
    *
    * 1.0.0
    * Legacy
    */

    private static $module;
    private static $command;
    private static $params = [];
    private static $options = [];

    /**
     * addParam
     *
     * @param $param
     */
    public static function addParam(string $name, $param)
    {
        if (!isset(self::$params[$name])) {
            self::$params[] = $param;
        }
    }
    /**
     * addOption
     *
     * @param $option
     */
    public static function addOption(string $name, $option)
    {
        if (!isset(self::$options[$name])) {
            self::$options[$name] = $option;
        }
    }
    /**
    * getModule
    *
    * @return $module
    */
    public static function getModule()
    {
        return self::$module;
    }
    /**
    * getCommand
    *
    * @return $command
    */
    public static function getCommand()
    {
        return self::$command;
    }
    /**
    * getParam
    *
    * @return $param
    */
    public static function getParam(?string $param)
    {
        if (!isset(self::$params[$param])) {
            throw new NotFound("Param \033[31m{$param}\033[0m not found in command");
        }

        return self::$params[$param] ?? null;
    }
    /**
    * getParams
    *
    * @return $params
    */
    public static function getParams()
    {
        return self::$params;
    }
    /**
    * getOption
    *
    * @return $option
    */
    public static function getOption($option)
    {
        if (!isset(self::$options[$option])) {
            throw new NotFound("Option \033[31m{$option}\033[0m not found in command");
        }

        return self::$options[$option] ?? null;
    }
    /**
    * getOptions
    *
    * @return $options
    */
    public static function getOptions()
    {
        return self::$options;
    }
    /**
     * setModule
     *
     * @param $module
     */
    public static function setModule($module)
    {
        self::$module = $module;
    }
    /**
     * setCommand
     *
     * @param $command
     */
    public static function setCommand($command)
    {
        self::$command = $command;
    }
    /**
     * setParams
     *
     * @param $params
     */
    public static function setParams($params)
    {
        self::$params = $params;
    }
    /**
     * setOptions
     *
     * @param $options
     */
    public static function setOptions($options)
    {
        self::$options = $options;
    }
}
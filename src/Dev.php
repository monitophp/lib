<?php
/**
 * Dev
 *
 * Development tools
 * @author Joelson B <joelsonb@msn.com>
 * @copyright Copyright &copy; 2015 - 2018
 *
 * @package \MonitoLib
 */
namespace MonitoLib;

use \MonitoLib\App;

class Dev
{
    const VERSION = '1.0.3';
    /**
    * 1.0.3 - 2020-09-18
    * fix: minor fixes
    *
    * 1.0.2 - 2020-07-24
    * fix: breakline now is echoing
    *
    * 1.0.1 - 2019-08-11
    * fix: vd => $index = 1
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	private static function breakLine() : void
	{
		echo App::isCli() ? PHP_EOL : '<br />';
	}
	public static function db(int $index = 1) : void
	{
		$db = debug_backtrace();
		echo (isset($db[$index + 1]) ? $db[$index + 1]['function'] . ', ' : '') . $db[$index]['file'] . ', ' . $db[$index]['line'];
		self::breakLine();
	}
	public static function e(string $string, bool $breakLine = true) : void
	{
		self::db();
		echo $string;

		if ($breakLine) {
			self::breakLine();
		}
	}
	public static function ee(string $string = 'exited') : void
	{
		self::db();
		echo $string;
		self::breakLine();
		exit;
	}
	public static function lme(object $class) : void
	{
		$methods = get_class_methods($class);
		sort($methods);
		self::pre($methods);
	}
	public static function pr($a, bool $exit = false, int $index = 1) : void
	{
		self::db($index);
		echo App::isCli() ? '' : '<pre>';
		print_r($a);

		if ($exit) {
			exit;
		} else {
			echo App::isCli() ? "\n" : '</pre>';
		}
	}
	public static function pre($a) : void
	{
		self::pr($a, true, 2);
	}
	private static function sliceArrayDepth(array $array, int $depth = 0) : array
	{
	    foreach ($array as $key => $value) {
	        if (is_array($value)) {
	            if ($depth > 0) {
	                $array[$key] = self::sliceArrayDepth($value, $depth - 1);
	            } else {
	                unset($array[$key]);
	            }
	        }
	    }

	    return $array;
	}
	public static function vd($a, int $depth = 0, bool $exit = false, int $index = 1) : void
	{
		self::db($index);

		if ($depth > 0) {
			$a = self::sliceArrayDepth($a, $depth);
		}

		echo App::isCli() ? '' : '<pre>';
		var_dump($a);

		if ($exit) {
			exit;
		} else {
			echo App::isCli() ? "\n" : '</pre>';
		}
	}
	public static function vde($a, int $depth = 0) : void
	{
		self::vd($a, $depth, true, 2);
	}
}
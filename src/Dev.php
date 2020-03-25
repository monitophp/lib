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

class Dev
{
    const VERSION = '1.0.1';
    /**
    * 1.0.1 - 2019-08-11
    * fix: vd => $index = 1
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	private static function breakLine ()
	{
		return self::isCli() ? "\n" : PHP_EOL;
	}
	public static function db ($index = 1)
	{
		$db = debug_backtrace();
		// print_r($db);
		echo (isset($db[$index + 1]) ? $db[$index + 1]['function'] . ', ' : '') . $db[$index]['file'] . ', ' . $db[$index]['line'] . self::breakLine();
	}
	public static function e ($s, $breakLine = true)
	{
		self::db();
		echo $s;

		if ($breakLine) {
			self::breakLine();
		}
	}
	public static function ee ($s = 'exited', $breakLine = true)
	{
		self::db();
		echo $s;
		self::breakLine();
		exit;
	}
	private static function isCli () {
		return PHP_SAPI === 'cli' ? true : false;
	}
	public static function lme ($class)
	{
		$methods = get_class_methods($class);
		sort($methods);
		self::pre($methods);
	}
	public static function pr ($a, $e = false, $index = 1)
	{
		self::db($index);
		echo self::isCli() ? '' : '<pre>';
		print_r($a);

		if ($e) {
			exit;
		} else {
			echo self::isCli() ? "\n" : '</pre>';
		}

	}
	public static function pre ($a)
	{
		self::pr($a, true, 2);
	}
	private static function sliceArrayDepth ($array, $depth = 0)
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
	public static function vd ($a, $depth = 0, $e = false, $index = 1)
	{
		self::db($index);

		if ($depth > 0) {
			$a = self::sliceArrayDepth($a, $depth);
		}

		echo self::isCli() ? '' : '<pre>';
		var_dump($a);

		if ($e) {
			exit;
		} else {
			echo self::isCli() ? "\n" : '</pre>';
		}
	}
	public static function vde ($a, $depth = 0)
	{
		self::vd($a, $depth, true, 2);
	}
}
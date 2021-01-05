<?php
namespace MonitoLib;

use MonitoLib\Exception\BadRequest;

class Response
{
    const VERSION = '2.1.0';
    /**
	* 2.1.0 - 2020-12-21
	* new: asJson(), asPdf(), parse(), parseString, render(), setDebug()
	*
    * 2.0.0 - 2020-09-18
    * new: static properties and methods
    * new: get/setContentType, get/setHttpResponseCode
    *
    * 1.1.0 - 2019-05-02
    * fix: checks $dataset['data']
    *
    * 1.0.0 - 2018-04-27
    * Inicial release
    */

	private static $contentType = 'Content-Type: application/json';
	private static $httpResponseCode;
	private static $debug = [];
	private static $return = [];

	public static function asJson() : void
	{
		self::$contentType = 'Content-Type: application/json';
	}
	public static function asPdf() : void
	{
		self::$contentType = 'Content-Type: application/pdf';
	}
	public static function getContentType()
	{
		return self::$contentType;
	}
	public static function getHttpResponseCode()
	{
		return self::$httpResponseCode;
	}
	public static function parse($value) : array
	{
		if ($value === '') {
			$value = null;
		}

		$return = [];

		switch (gettype($value)) {
			case 'array':
				$return = Response::toArray($value);
				break;
			case 'NULL':
				$return = [];
				break;
			case 'boolean':
			case 'double':
			case 'integer':
				$return[] = $value;
				break;
			case 'object':
				$return = Response::toArray($value);
				break;
			case 'string':
				$return = Response::parseString($value);
				break;
			case 'resource (closed)':
			case 'resource':
			case 'unknown type':
			default:
				throw new BadRequest('Invalid json');
		}

		return self::$return = $return;
	}
	public static function parseString(string $string) : array
	{
		$json = json_decode($string, true);

		if ($json && $string != $json) {
			$return = $json;
		} else {
			$return = [$string];
		}

		return $return;
	}
	public static function render()
	{
		http_response_code(self::$httpResponseCode);
		header(self::$contentType);

		if (empty(self::$return)) {
			http_response_code(204);
		} else {
			try {
				if (!empty(self::$debug)) {
					self::$return['debug'] = self::$debug;
				}

				$output = json_encode(self::$return, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
			} catch (\Exception | \ThrowAble $e) {
				$output = json_encode(['message' => $e->getMessage()]);
			} finally {
				echo $output;
			}
		}
	}
	public static function setContentType($contentType)
	{
		self::$contentType = $contentType;
	}
	public static function setDebug(array $debug)
	{
		self::$debug = $debug;
	}
	public static function setHttpResponseCode($httpResponseCode)
	{
		self::$httpResponseCode = $httpResponseCode;
	}
	public static function toArray($object)
	{
		$results = [];

		if (is_array($object)) {
			foreach ($object as $k => $o) {
				$results[$k] = self::toArray($o);
			}
		} else if (is_object($object)) {
			if ($object instanceof \stdClass) {
				$results = json_decode(json_encode($object), true);
			} else {
				$result = [];
				$class  = new \ReflectionClass(get_class($object));

			    foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
			        $methodName = $method->name;

			        if (strpos($methodName, 'get') === 0 && strlen($methodName) > 3) {
			            $propertyName = lcfirst(substr($methodName, 3));
			            $value = $method->invoke($object);

			            if (is_object($value)) {
		                    $result[$propertyName] = self::toArray($value);
			            } else {
			                $result[$propertyName] = $value ?? '';
			            }
			        }
			    }

			    $results = $result;
			}
		} else {
			$results = $object;
		}

		return $results;
	}
}
<?php
namespace MonitoLib;

use \MonitoLib\Functions;

class Response
{
    const VERSION = '2.0.0';
    /**
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
	private static $httpResponseCode = 500;
	private static $json = [];

	public static function getContentType()
	{
		return self::$contentType;
	}
	public static function getHttpResponseCode()
	{
		return self::$httpResponseCode;
	}
	public static function render()
	{
		http_response_code(self::$httpResponseCode);

		if (!empty(self::$json)) {
			return json_encode(self::$json, JSON_UNESCAPED_UNICODE);
		}
	}
	public static function setContentType($contentType)
	{
		self::$contentType = $contentType;
		return self;
	}
	public static function setData($data)
	{
		if (is_object($data)) {
			if (!$data instanceof \stdClass) {
				$data = self::toArray($data);
			}
		}

		self::$json['data'] = $data;
		return self;
	}
	public static function setDataset($dataset)
	{
		if (isset($dataset['data'])) {
			$dataset['data'] = self::toArray($dataset['data']);
		}

		self::$json = Functions::arrayMergeRecursive(self::$json, $dataset);
		return self;
	}
	public static function setHttpResponseCode($httpResponseCode)
	{
		self::$httpResponseCode = $httpResponseCode;
		return self;
	}
	public static function setJson($json)
	{
		self::$json = $json;
		return self;
	}
	public static function setMessage($message)
	{
		self::$json['message'] = $message;
		return self;
	}
	public static function setProperty($property, $value)
	{
		self::$json[$property] = is_null($value) ? '' : $value;
		return self;
	}
	public static function toArray($object)
	{
		$results = [];

		if (is_array($object)) {
			foreach ($object as $k => $o) {
				$results[$k] = self::toArray($o);
			}
		} else if (is_object($object)) {
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
		} else {
			$results = $object;
		}

		return $results;
	}
}
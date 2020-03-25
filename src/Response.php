<?php
/**
 * 1.0.0. - 2018-04-27
 * Inicial release
 */
namespace MonitoLib;

use \MonitoLib\Functions;

class Response
{
    const VERSION = '1.1.0';
    /**
    * 1.1.0 - 2019-05-02
    * fix: checks $dataset['data']
    *
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	static private $instance;

	private $json = [];
	private $httpResponseCode = 200;

	private function __construct ()
	{
	}
	public function __toString ()
	{
		return $this->render();
	}
	public static function getInstance ()
	{
		if (!isset(self::$instance)) {
			self::$instance = new \MonitoLib\Response;
		}

		return self::$instance;
	}
	private function render ()
	{
		http_response_code($this->httpResponseCode);

		if (!empty($this->json)) {
			return json_encode($this->json, JSON_UNESCAPED_UNICODE);
		}
	}
	public function setData ($data)
	{
		if (is_object($data)) {
			if (!$data instanceof \stdClass) {
				$data = $this->toArray($data);
			}
		}

		$this->json['data'] = $data;
		return $this;
	}
	public function setDataset ($dataset)
	{
		if (isset($dataset['data'])) {
			$dataset['data'] = $this->toArray($dataset['data']);
		}

		$this->json = Functions::arrayMergeRecursive($this->json, $dataset);
		return $this;
	}
	public function setHttpResponseCode ($httpResponseCode)
	{
		$this->httpResponseCode = $httpResponseCode;
		return $this;
	}
	public function setJson ($json)
	{
		$this->json = $json;
		return $this;
	}
	public function setMessage ($message)
	{
		$this->json['message'] = $message;
		return $this;
	}
	public function setProperty ($property, $value)
	{
		$this->json[$property] = is_null($value) ? '' : $value;
		return $this;
	}
	public function toArray ($objectList)
	{
		if (is_null($objectList)) {
			return [];
		}

		$objectListOk = [];

		if (is_array($objectList)) {
			$objectListOk = $objectList;
		} else {
			$objectListOk[] = $objectList;
		}

		$results = [];

		foreach ($objectListOk as $object) {
			$result = [];
		    $class = new \ReflectionClass(get_class($object));
		    
		    foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
		        $methodName = $method->name;

		        if (strpos($methodName, 'get') === 0 && strlen($methodName) > 3) {
		            $propertyName = lcfirst(substr($methodName, 3));
		            $value = $method->invoke($object);

		            if (is_object($value)) {
	                    $result[$propertyName] = $this->toArray($value);
		            } else {
		                $result[$propertyName] = $value ?? '';
		            }
		        }
		    }

		    $results[] = $result;
		}
		if (is_array($objectList)) {
	    	return $results;
		} else {
			return $results[0];
		}
	}
}
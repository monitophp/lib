<?php

namespace MonitoLib;

use \MonitoLib\Exception\BadRequest;
use \MonitoLib\Exception\NotFound;
use \MonitoLib\Request;
use \MonitoLib\Response;

class Controller
{
	const VERSION = '2.0.0';
	/**
	 * 2.0.0 - 2020-09-18
	 * new: overwrite for CRUD methods
	 * new: static Response/Request
	 *
	 * 1.0.0 - 2019-04-17
	 * first versioned
	 */

	private $dao;
	private $daoName;
	private $dataset;
	private $dto;
	private $dtoName;
	private $fields;
	private $model;
	private $modelName;
	private $orderBy;
	private $page;
	private $perPage;
	private $query;

	protected $daoClass;
	protected $notFound;

	public function __construct()
	{
		if (is_null($this->daoClass)) {
			$classParts = explode('\\', get_class($this));
		} else {
			$classParts = explode('\\', $this->daoClass);
			// \MonitoLib\Dev::pre($classParts);
		}

		$namespace = join('\\', array_slice($classParts, 0, -2)) . '\\';
		$className = end($classParts);
		// \MonitoLib\Dev::pre($this);

		$this->daoName   = $namespace . 'Dao\\' . $className;
		$this->dtoName   = $namespace . 'Dto\\' . $className;
		$this->modelName = $namespace . 'Model\\' . $className;
	}

	public function __call(string $method, $arguments)
	{
		if (!method_exists($this, $method)) {
			throw new NotFound("Method $method doesn't exists");
		}

		return $this->$method(...$arguments);
	}
	private function create($mix = null)
	{
		if (is_null($mix)) {
			$json[] = Request::getJson();
		} else {
			if (is_array($mix)) {
				$json = $mix;
			} else {
				$json[] = $mix;
			}
		}

		$dao = new $this->daoName;

		foreach ($json as $j) {
			$dto = $this->jsonToDto($j);
			$dao->insert($dto);
		}

		Response::setHttpResponseCode(201);
	}
	private function delete(...$keys)
	{
		// if (empty($keys)) {
		// 	throw new BadRequest('Não é possível deletar sem parâmetros');
		// }

		$dao   = $this->getDao();
		$model = $this->getModel();
		// $model = $this->getModel();

		// if (!empty($keys)) {
		// 	$primaryKeys = $model->getPrimaryKeys();

		// 	$i = 0;

		// 	foreach ($primaryKeys as $column) {
		// 		$name = $column->getName();
		// 		$dao->equal($name, $keys[$i], $dao::FIXED_QUERY);
		// 		$i++;
		// 	}
		// }

		if (empty($keys)) {
			$query = Request::getQuery($model, $dao);
			$dao->mergeFilter($query);
		}

		$dao->delete(...$keys);

		Response::setHttpResponseCode(204);
	}
	private function get(...$keys)
	{
		$dao   = $this->getDao();
		$model = $this->getModel();

		if (empty($keys)) {
			$query = Request::getQuery($model, $dao);
			$dao->mergeFilter($query);
			return $dao->dataset();
		}

		$primaryKeys = $model->getPrimaryKeys();

		$i = 0;

		foreach ($primaryKeys as $field) {
			$dao->equal($field->getId(), $keys[$i], $dao::FIXED);
			$i++;
		}

		$dto = $dao->get();

		if (is_null($dto)) {
			throw new NotFound($this->notFound ?? 'Registro não encontrado');
		}

		return $dto;
	}
	private function update(...$keys)
	{
		$json  = Request::getJson();
		$dao   = $this->getDao();
		$model = $this->getModel();

		// if (!empty($keys)) {
		// 	$primaryKeys = $model->getPrimaryKeys();

		// 	$i = 0;

		// 	foreach ($primaryKeys as $field) {
		// 		$dao->equal($field, $keys[$i], $dao::FIXED);
		// 		$i++;
		// 	}
		// }

		$dto = $this->_get(...$keys);

		if (is_null($dto)) {
			throw new NotFound($this->notFound ?? 'Registro não encontrado');
		}

		$dto = $this->jsonToDto($dto, $json);

		// \MonitoLib\Dev::vde($dto);


		$dao->update($dto);

		Response::setHttpResponseCode(201);
	}
	public function getDao()
	{
		if (is_null($this->dao)) {
			$this->dao = new $this->daoName;
		}

		return $this->dao;
	}
	public function getModel()
	{
		if (is_null($this->model)) {
			$this->model = new $this->modelName;
		}

		return $this->model;
	}
	public function jsonToDto(object $json)
	{
		// $dao   = $this->getDao();
		$model = $this->getModel();
		$dto   = new $this->dtoName;

		// \MonitoLib\Dev::vde($json);
		// \MonitoLib\Dev::pr($dto);
		if (!is_null($json)) {
			foreach ($json as $k => $v) {
				$column = $model->getColumn($k);
				$type   = $column->getType();

				switch ($type) {
					case 'date':
					case 'datetime':
					case 'time':
						$v = new \MonitoLib\Type\DateTime($v);
						break;
				}

				// \MonitoLib\Dev::pre($column);

				$method = 'set' . \MonitoLib\Functions::toUpperCamelCase($k);
				if (method_exists($dto, $method)) {
					// \MonitoLib\Dev::e($method);
					// \MonitoLib\Dev::vd($v);

					$dto->$method($v);
				}
			}
		}

		return $dto;
	}
	public function toNull($value)
	{
		return $value === '' ? null : $value;
	}
}

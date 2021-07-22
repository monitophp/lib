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

	protected $notFound;

	public function __construct()
	{
		$classParts      = explode('\\', get_class($this));
		$namespace       = join('\\', array_slice($classParts, 0, -2)) . '\\';
		$className       = end($classParts);
		$this->daoName   = $namespace . 'Dao\\' . $className;
		$this->dtoName   = $namespace . 'Dto\\' . $className;
		$this->modelName = $namespace . 'Model\\' . $className;
	}

	public function __call($name, $arguments)
	{
		$method = '_' . $name;

		if (!method_exists($this, $method)) {
	        throw new NotFound("Method $name doesn't exists");
		}

		return $this->$method(...$arguments);
	}
	public function _create($mix = null)
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
		    $dto = $this->jsonToDto(new $this->dtoName, $j);
		    $dao->insert($dto);
		}

	    Response::setHttpResponseCode(201);
	}
	public function _delete(...$keys)
	{
		if (empty($keys)) {
			throw new BadRequest('Não é possível deletar sem parâmetros');
		}

	    $dao   = $this->getDao();
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

		$dao->delete(...$keys);

	    Response::setHttpResponseCode(204);
	}
	public function _get(...$keys)
	{
	    $dao   = $this->getDao();
		$model = $this->getModel();

		if (!empty($keys)) {
			$primaryKeys = $model->getPrimaryKeys();

			$i = 0;

			foreach ($primaryKeys as $field) {
				$dao->equal($field, $keys[$i], $dao::FIXED_QUERY);
				$i++;
			}
		}

		$query = Request::getQuery($model, $dao);
		$dao->mergeFilter($query);

		// \MonitoLib\Dev::pre($query);

		// $query = Request::getFilter();

		// $dataset = $this->dataset ?? Request::asDataset();
		// $fields  = $this->fields  ?? Request::getFields();
		// $orderBy = $this->orderBy ?? Request::getOrderBy();
		// $page    = $this->page    ?? Request::getPage();
		// $perPage = $this->perPage ?? Request::getPerPage();
		// $query   = $this->query   ?? Request::getQuery();

	    // $dao->fields($fields)
	    // 	->setQuery($query);

	    if (empty($keys)) {
    	    // $dao->setPerPage($perPage)
    	        // ->setPage($page)
	        	// ->setOrderBy($orderBy);

    	    // if ($dataset) {
    	        // return $dao->dataset();
    	    // } else {
    	        return $dao->dataset();
    	    // }
	    } else {
    	    $dto = $dao->get();

    	    if (is_null($dto)) {
    	        throw new NotFound($this->notFound ?? 'Registro não encontrado');
    	    }

    	    return $dto;
    	}
	}
	public function _update(...$keys)
	{
		$json  = Request::getJson();
		$dao   = $this->getDao();
		$model = $this->getModel();

		if (!empty($keys)) {
			$primaryKeys = $model->getPrimaryKeys();

			$i = 0;

			foreach ($primaryKeys as $field) {
				$dao->equal($field, $keys[$i], $dao::FIXED_QUERY);
				$i++;
			}
		}

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
	public function jsonToDto($dto, $json)
	{
		if (!is_null($json)) {
			foreach ($json as $k => $v) {
				$method = 'set' . \MonitoLib\Functions::toUpperCamelCase($k);
				if (method_exists($dto, $method)) {
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

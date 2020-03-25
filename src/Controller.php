<?php
namespace MonitoLib;

use \MonitoLib\Exception\NotFound;

class Controller
{
    const VERSION = '1.0.0';
    /**
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

	protected $request;
	protected $response;

	public function __construct()
	{
		$this->request  = \MonitoLib\Request::getInstance();
		$this->response = \MonitoLib\Response::getInstance();

		$classParts  = explode('\\', get_class($this));
		$namespace   = join(array_slice($classParts, 0, -2), '\\') . '\\';
		$className   = end($classParts);
		$this->daoName   = $namespace . 'Dao\\' . $className;
		$this->dtoName   = $namespace . 'Dto\\' . $className;
		$this->modelName = $namespace . 'Model\\' . $className;
	}
	public function create($mix = null) : void
	{
		if (is_null($mix)) {
	    	$json[] = $this->request->getJson();
		} else {
			if (is_array($mix)) {
				$json = $mix;
			} else {
				$json[] = $mix;
			}
		}

		foreach ($json as $j) {
		    $dao = new $this->dao;
		    $dto = $this->jsonToDto(new $this->dto, $j);
		    $dao->insert($dto);
		}

	    $this->response->setHttpResponseCode(201);
	}
	public function delete(...$mix)
	{
	    $dao = new $daoName();
	    $deleted = $secUserDao->andEqual('id', $id)
	        ->delete();

	    $this->response->setHttpResponseCode(204);
	}
	public function get(...$keys)
	{
		// \MonitoLib\Dev::pre($mix);
		\MonitoLib\Dev::pre($_REQUEST['route']);


	    $dao   = $this->getDao();
		$model = $this->getModel();

		if (!empty($keys)) {
			$primaryKeys = $model->getPrimaryKeys();

			// \MonitoLib\Dev::pre($primaryKeys);

			$i = 0;

			foreach ($primaryKeys as $field) {
				$dao->andEqual($field, $keys[$i], $dao::FIXED_QUERY);
				$i++;
			}
		}

		$dataset = $this->dataset ?? $this->request->asDataset();
		$fields  = $this->fields ?? $this->request->getFields();
		$orderBy = $this->orderBy ?? $this->request->getOrderBy();
		$page    = $this->page ?? $this->request->getPage();
		$perPage = $this->perPage ?? $this->request->getPerPage();
		$query   = $this->query ?? $this->request->getQuery();

	    $dao->setFields($fields)
	    	->setQuery($query);

    	if ($perPage > 0) {
    	    $dao->setPerPage($perPage)
    	        ->setPage($page)
	        	->setOrderBy($orderBy);

    	    if ($dataset) {
    	        $ds = $dao->dataset();
    	        $this->response->setDataset($ds);
    	    } else {
    	        $list = $dao->list();
    	        $this->response->setData($this->response->toArray($list));
    	    }
    	} else {
    	    $dto = $dao->get();

    	    if (is_null($dto)) {
    	        throw new NotFound('Registro não encontrado!');
    	    }

    	    $this->response->setData($dto);
    	}
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
	public function update($mix)
	{
		if (is_null($mix)) {
	    	$json[] = $this->request->getJson();
		} else {
			if (is_array($mix)) {
				$json = $mix;
			} else {
				$json[] = $mix;
			}
		}

	    $secUserDao = new \App\Dao\SecUser;
	    $secUserDto = $secUserDao->andEqual('id', $id)
	        ->get();

	    if (is_null($secUserDto)) {
	        throw new NotFound('Registro não encontrado!');
	    }

	    $this->jsonToDto($secUserDto, $json);
	    $secUserDao->update($secUserDto);

	    $this->response->setMessage('Registro atualizado com sucesso!')
	         ->setHttpResponseCode(200);
	}
}
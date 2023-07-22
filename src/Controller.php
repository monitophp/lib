<?php
/**
 * Controller.
 *
 * @version 2.0.1
 */

namespace MonitoLib;

use MonitoLib\Exception\BadRequestException;
use MonitoLib\Exception\NotFoundException;

class Controller
{
    protected $notFound;

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

    public function __construct()
    {
        $classParts = explode('\\', get_class($this));
        $namespace = join('\\', array_slice($classParts, 0, -2)) . '\\';
        $className = end($classParts);
        $this->daoName = $namespace . 'Dao\\' . $className;
        $this->dtoName = $namespace . 'Dto\\' . $className;
        $this->modelName = $namespace . 'Model\\' . $className;
    }

    public function __call($name, $arguments)
    {
        $method = '_' . $name;

        if (!method_exists($this, $method)) {
            throw new NotFoundException("Method doesn't exists: {$name}");
        }

        return $this->{$method}(...$arguments);
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

        $dao = new $this->daoName();

        foreach ($json as $j) {
            $dto = $this->jsonToDto(new $this->dtoName(), $j);
            $dao->insert($dto);
        }

        Response::setHttpResponseCode(201);
    }

    public function _delete(...$keys)
    {
        if (empty($keys)) {
            throw new BadRequestException('Invalid parameters');
        }

        $dao = $this->getDao();
        $model = $this->getModel();

        if (!empty($keys)) {
            $primaryKeys = $model->getPrimaryKeys();

            $i = 0;

            foreach ($primaryKeys as $field) {
                $dao->equal($field, $keys[$i], $dao::FIXED_QUERY);
                ++$i;
            }
        }

        $dao->delete();

        Response::setHttpResponseCode(204);
    }

    public function _get(...$keys)
    {
        $dao = $this->getDao();
        $model = $this->getModel();

        if (!empty($keys)) {
            $primaryKeys = $model->getPrimaryKeys();

            $i = 0;

            foreach ($primaryKeys as $field) {
                $dao->equal($field, $keys[$i], $dao::FIXED_QUERY);
                ++$i;
            }
        }

        $dataset = $this->dataset ?? Request::asDataset();
        $fields = $this->fields ?? Request::getFields();
        $orderBy = $this->orderBy ?? Request::getOrderBy();
        $page = $this->page ?? Request::getPage();
        $perPage = $this->perPage ?? Request::getPerPage();
        $query = $this->query ?? Request::getQuery();

        $dao->setFields($fields)
            ->setQuery($query)
        ;

        if (empty($keys)) {
            $dao->setPerPage($perPage)
                ->setPage($page)
                ->setOrderBy($orderBy)
            ;

            if ($dataset) {
                return $dao->dataset();
            }

            return $dao->list();
        }
        $dto = $dao->get();

        if (is_null($dto)) {
            throw new NotFoundException($this->notFound ?? 'Resource not found');
        }

        return $dto;
    }

    public function _update(...$keys)
    {
        $json = Request::getJson();
        $dao = $this->getDao();
        $model = $this->getModel();

        if (!empty($keys)) {
            $primaryKeys = $model->getPrimaryKeys();

            $i = 0;

            foreach ($primaryKeys as $field) {
                $dao->equal($field, $keys[$i], $dao::FIXED_QUERY);
                ++$i;
            }
        }

        $dto = $this->_get(...$keys);

        if (is_null($dto)) {
            throw new NotFoundException($this->notFound ?? 'Resource not found');
        }

        $dto = $this->jsonToDto($dto, $json);
        $dao->update($dto);

        Response::setHttpResponseCode(201);
    }

    public function getDao()
    {
        if (is_null($this->dao)) {
            $this->dao = new $this->daoName();
        }

        return $this->dao;
    }

    public function getModel()
    {
        if (is_null($this->model)) {
            $this->model = new $this->modelName();
        }

        return $this->model;
    }

    public function jsonToDto($dto, $json)
    {
        if (!is_null($json)) {
            foreach ($json as $k => $v) {
                $method = 'set' . to_upper_camel_case($k);

                if (method_exists($dto, $method)) {
                    $dto->{$method}($v);
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

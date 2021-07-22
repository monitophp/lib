<?php
namespace MonitoLib\Database\Dataset;

class Dataset
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-04-22
    * Initial version
    */

    private $data;
    private $pagination;
    private $query;

    public function __construct(array $data, Pagination $pagination)
    {
		$this->data = $data;
		$this->pagination = $pagination;
    }
	/**
	* getData
	*
	* @return $data
	*/
	public function getData()
	{
		return $this->data;
	}
	/**
	* getPagination
	*
	* @return $pagination
	*/
	public function getPagination()
	{
		return $this->pagination;
	}
}
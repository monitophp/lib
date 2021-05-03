<?php
namespace MonitoLib\Database;

class Dataset
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-04-22
    * Initial version
    */

    private $count;
    private $data;
    private $page;
    private $perPage;
    private $showing;
    private $total;
    private $pages;

    public function __construct(int $total, int $count, int $page, int $perPage, array $data)
    {
		$perPage = $perPage === 0 ? 1 : $perPage;
        $this->total   = $total;
        $this->count   = $count;
        $this->page    = $page;
        $this->perPage = $perPage;
        $this->data    = $data;
        $this->showing = count($data);
        $this->pages   = ceil($count / $perPage);
    }
	/**
	* getCount
	*
	* @return $count
	*/
	public function getCount()
	{
		return $this->count;
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
	* getPage
	*
	* @return $page
	*/
	public function getPage()
	{
		return $this->page;
	}
	/**
	* getPages
	*
	* @return $pages
	*/
	public function getPages()
	{
		return $this->pages;
	}
	/**
	* getPerPage
	*
	* @return $perPage
	*/
	public function getPerPage()
	{
		return $this->perPage;
	}
	/**
	* getShowing
	*
	* @return $showing
	*/
	public function getShowing()
	{
		return $this->showing;
	}
	/**
	* getTotal
	*
	* @return $total
	*/
	public function getTotal()
	{
		return $this->total;
	}
}
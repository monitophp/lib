<?php
namespace MonitoLib\Database\Dataset;

class Pagination
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-05-14
    * Initial version
    */

    private $count;
    private $page;
    private $perPage;
    private $showing;
    private $total;
    private $pages;

    public function __construct(int $total, int $count, int $page, int $perPage, int $showing)
    {
        $this->total   = $total;
        $this->count   = $count;
        $this->page    = $page;
        $this->perPage = $perPage === 0 ? 0 : $perPage;
        $this->showing = $showing;
        $this->pages   = $perPage > 0 ? ceil($count / $perPage) : 0;
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
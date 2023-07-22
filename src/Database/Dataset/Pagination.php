<?php
/**
 * Database\Dataset\Pagination.
 *
 * @version 1.0.1
 */

namespace MonitoLib\Database\Dataset;

class Pagination
{
    private $count;
    private $page;
    private $perPage;
    private $showing;
    private $total;
    private $pages;

    public function __construct(int $total, int $count, int $page, int $perPage, int $showing)
    {
        $this->total = $total;
        $this->count = $count;
        $this->page = $page;
        $this->perPage = $perPage === 0 ? 1 : $perPage;
        $this->showing = $showing;
        $this->pages = ceil($count / $perPage);
    }

    public function getCount()
    {
        return $this->count;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getPages()
    {
        return $this->pages;
    }

    public function getPerPage()
    {
        return $this->perPage;
    }

    public function getShowing()
    {
        return $this->showing;
    }

    public function getTotal()
    {
        return $this->total;
    }
}

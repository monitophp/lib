<?php
/**
 * Database\Dataset\Dataset.
 *
 * @version 1.0.1
 */

namespace MonitoLib\Database\Dataset;

class Dataset
{
    private $data;
    private $pagination;

    public function __construct(array $data, Pagination $pagination)
    {
        $this->data = $data;
        $this->pagination = $pagination;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getPagination()
    {
        return $this->pagination;
    }
}

<?php
namespace MonitoLib\Database\Query;

class OrderBy
{
    const VERSION = '1.0.0';
    /**
    * 1.0.0 - 2021-07-09
    * Initial release
    */

    private $page    = 1;
    private $perPage = 0;
    private $fields  = [];
    private $map     = [];
    private $where   = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having  = [];
}
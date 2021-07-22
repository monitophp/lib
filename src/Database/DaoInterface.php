<?php
namespace MonitoLib\Database;

interface DaoInterface
{
    /**
    * 1.1.0 - 2021-07-06
    * new: add replace(), rem getById
	*
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	public function count();
	public function dataset();
	public function delete(...$params);
	public function get(...$params);
	public function getLastId();
	public function insert($dto);
	public function list();
	public function replace($dto);
	public function truncate();
	public function update($dto);
}
<?php
namespace MonitoLib\Database;

interface Dao
{
    /**
    * 1.0.0 - 2019-04-17
    * first versioned
    */

	public function count ();
	public function dataset ();
	public function delete (...$params);
	public function get ();
	public function getById (...$params);
	public function getLastId ();
	public function insert ($dto);
	public function list ();
	public function truncate ();
	public function update ($dto);
}
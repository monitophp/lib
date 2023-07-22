<?php
/**
 * Database\Dao interface.
 *
 * @version 1.0.1
 */

namespace MonitoLib\Database;

interface Dao
{
    public function count();

    public function dataset();

    public function delete(...$params);

    public function get();

    public function getById(...$params);

    public function getLastId();

    public function insert($dto);

    public function list();

    public function truncate();

    public function update($dto);
}

<?php
namespace MonitoLib\Database\Model;

class Constraint
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2021-06-30
     * Initial release
     */

	private $name;
	private $type;
	private $database;
	private $table;
	private $column;
	private $position;
	private $referencedDatabase;
	private $referencedTable;
	private $referencedColumn;
	private $referencedObject;

	/**
	* getColumn
	*
	* @return $column
	*/
	public function getColumn()
	{
		return $this->column;
	}
	/**
	* getDatabase
	*
	* @return $database
	*/
	public function getDatabase()
	{
		return $this->database;
	}
	/**
	* getName
	*
	* @return $name
	*/
	public function getName()
	{
		return $this->name;
	}
	/**
	* getPosition
	*
	* @return $position
	*/
	public function getPosition()
	{
		return $this->position;
	}
	/**
	* getReferencedColumn
	*
	* @return $referencedColumn
	*/
	public function getReferencedColumn()
	{
		return $this->referencedColumn;
	}
	/**
	* getReferencedDatabase
	*
	* @return $referencedDatabase
	*/
	public function getReferencedDatabase()
	{
		return $this->referencedDatabase;
	}
	/**
	* getReferencedObject
	*
	* @return $referencedObject
	*/
	public function getReferencedObject()
	{
		return $this->referencedObject;
	}
	/**
	* getReferencedTable
	*
	* @return $referencedTable
	*/
	public function getReferencedTable()
	{
		return $this->referencedTable;
	}
	/**
	* getTable
	*
	* @return $table
	*/
	public function getTable()
	{
		return $this->table;
	}
	/**
	* getType
	*
	* @return $type
	*/
	public function getType()
	{
		return $this->type;
	}
	/**
	 * setColumn
	 *
	 * @param $column
	 */
	public function setColumn($column)
	{
		$this->column = $column;
		return $this;
	}
	/**
	 * setDatabase
	 *
	 * @param $database
	 */
	public function setDatabase($database)
	{
		$this->database = $database;
		return $this;
	}
	/**
	 * setName
	 *
	 * @param $name
	 */
	public function setName($name)
	{
		$this->name = $name;
		return $this;
	}
	/**
	 * setPosition
	 *
	 * @param $position
	 */
	public function setPosition($position)
	{
		$this->position = $position;
		return $this;
	}
	/**
	 * setReferencedColumn
	 *
	 * @param $referencedColumn
	 */
	public function setReferencedColumn($referencedColumn)
	{
		$this->referencedColumn = $referencedColumn;
		return $this;
	}
	/**
	 * setReferencedDatabase
	 *
	 * @param $referencedDatabase
	 */
	public function setReferencedDatabase($referencedDatabase)
	{
		$this->referencedDatabase = $referencedDatabase;
		return $this;
	}
	/**
	 * setReferencedObject
	 *
	 * @param $referencedObject
	 */
	public function setReferencedObject($referencedObject)
	{
		$this->referencedObject = $referencedObject;
		return $this;
	}
	/**
	 * setReferencedTable
	 *
	 * @param $referencedTable
	 */
	public function setReferencedTable($referencedTable)
	{
		$this->referencedTable = $referencedTable;
		return $this;
	}
	/**
	 * setTable
	 *
	 * @param $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
		return $this;
	}
	/**
	 * setType
	 *
	 * @param $type
	 */
	public function setType($type)
	{
		$this->type = $type;
		return $this;
	}
}
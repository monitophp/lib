<?php
namespace MonitoLib\Database\Model;

class Table
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2021-06-30
     * Initial release
     */

	private $name;
	private $database;
	private $alias;
	private $class;
	private $object;
	private $plural;
	private $singular;
	private $prefix;
	private $type = 'table';
    private $columns = [];
    private $constraints = [];

    /**
     * addColumn
     *
     * @param $column
     */
    public static function addColumn(Column $column)
    {
        self::$columns = array_map(function($e) use ($column) {
            if ($e->getId() === $column->getId()) {
                return $column;
            }

            return $e;
        }, self::$columns);
    }
    /**
     * addConstraint
     *
     * @param $constraint
     */
    public static function addConstraint(Constraint $constraint)
    {
        self::$constraints = array_map(function($e) use ($constraint) {
            if ($e->getName() === $constraint->getName() && $e->getPosition() === $constraint->getPosition()) {
                return $constraint;
            }

            return $e;
        }, self::$constraints);
    }
	/**
	* getAlias
	*
	* @return $alias
	*/
	public function getAlias()
	{
		return $this->alias;
	}
	/**
	* getClass
	*
	* @return $class
	*/
	public function getClass() : string
	{
		return $this->class;
	}
	/**
	* getColumns
	*
	* @return $columns
	*/
	public function getColumns()
	{
		return $this->columns;
	}
	/**
	* getConstraints
	*
	* @return $constraints
	*/
	public function getConstraints()
	{
		return $this->constraints;
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
	* getObject
	*
	* @return $object
	*/
	public function getObject()
	{
		return $this->object;
	}
	/**
	* getPlural
	*
	* @return $plural
	*/
	public function getPlural()
	{
		return $this->plural;
	}
	/**
	* getPrefix
	*
	* @return $prefix
	*/
	public function getPrefix()
	{
		return $this->prefix;
	}
	/**
	* getSingular
	*
	* @return $singular
	*/
	public function getSingular()
	{
		return $this->singular;
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
	 * setAlias
	 *
	 * @param $alias
	 */
	public function setAlias($alias)
	{
		$this->alias = $alias;
		return $this;
	}
	/**
	 * setClass
	 *
	 * @param $class
	 */
	public function setClass($class)
	{
		$this->class = $class;
		return $this;
	}
	/**
	 * setColumns
	 *
	 * @param $columns
	 */
	public function setColumns($columns)
	{
		$this->columns = $columns;
		return $this;
	}
	/**
	 * setConstraints
	 *
	 * @param $constraints
	 */
	public function setConstraints($constraints)
	{
		$this->constraints = $constraints;
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
	 * setObject
	 *
	 * @param $object
	 */
	public function setObject($object)
	{
		$this->object = $object;
		return $this;
	}
	/**
	 * setPlural
	 *
	 * @param $plural
	 */
	public function setPlural($plural)
	{
		$this->plural = $plural;
		return $this;
	}
	/**
	 * setPrefix
	 *
	 * @param $prefix
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
		return $this;
	}
	/**
	 * setSingular
	 *
	 * @param $singular
	 */
	public function setSingular($singular)
	{
		$this->singular = $singular;
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
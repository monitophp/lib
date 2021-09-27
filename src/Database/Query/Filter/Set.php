<?php

namespace MonitoLib\Database\Query\Filter;

class Set
{
	const VERSION = '1.0.0';
	/**
	 * 1.0.0 - 2021-09-16
	 * Initial release
	 */

	private $column;
	private $value;
	private $options;

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
	 * getColumn
	 *
	 * @return $column
	 */
	public function getColumn()
	{
		return $this->column;
	}
	/**
	 * getComparison
	 *
	 * @return $comparison
	 */
	public function getComparison()
	{
		return $this->comparison;
	}
	/**
	 * getFormat
	 *
	 * @return $format
	 */
	public function getFormat()
	{
		return $this->format;
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
	 * getOptions
	 *
	 * @return $options
	 */
	public function getOptions()
	{
		return $this->options;
	}
	/**
	 * getParsedValue
	 *
	 * @return $parsedValue
	 */
	public function getParsedValue()
	{
		return $this->parsedValue;
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
	 * getValue
	 *
	 * @return $value
	 */
	public function getValue()
	{
		return $this->value;
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
	 * setComparison
	 *
	 * @param $comparison
	 */
	public function setComparison($comparison)
	{
		$this->comparison = $comparison;
		return $this;
	}
	/**
	 * setFormat
	 *
	 * @param $format
	 */
	public function setFormat($format)
	{
		$this->format = $format;
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
	 * setOptions
	 *
	 * @param $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
		return $this;
	}
	/**
	 * setParsedValue
	 *
	 * @param $parsedValue
	 */
	public function setParsedValue($parsedValue)
	{
		$this->parsedValue = $parsedValue;
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
	/**
	 * setValue
	 *
	 * @param $value
	 */
	public function setValue($value)
	{
		$this->value = $value;
		return $this;
	}
}

<?php

namespace MonitoLib\Database\Model;

class Column
{
	const VERSION = '1.0.0';
	/**
	 * 1.0.0 - 2021-04-21
	 * Initial releasae
	 */

	private $id;
	private $name;
	private bool $auto = false;
	private $source;
	private $type = 'string';
	private ?string $format;
	private $charset = 'utf8';
	private $collation = 'utf8_general_ci';
	private $alias;
	private $default;
	private $label = '';
	private $maxLength = 0;
	private $minLength = 0;
	private $maxValue = 0;
	private $minValue = 0;
	private $precision;
	private $restrict = [];
	private $scale;
	private $primary = false;
	private $required = false;
	private $transform = [];
	private $unique = false;
	private $unsigned = false;
	private $onInsert;
	private $onUpdate;
	private bool $query = false;

	/**
	 * getAlias
	 *
	 * @return $alias
	 */
	public function getAlias(): ?string
	{
		return $this->alias;
	}
	/**
	 * getAuto
	 *
	 * @return $auto
	 */
	public function getAuto(): bool
	{
		return $this->auto;
	}
	/**
	 * getCharset
	 *
	 * @return $charset
	 */
	public function getCharset()
	{
		return $this->charset;
	}
	/**
	 * getCollation
	 *
	 * @return $collation
	 */
	public function getCollation()
	{
		return $this->collation;
	}
	/**
	 * getDefault
	 *
	 * @return $default
	 */
	public function getDefault()
	{
		return $this->default;
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
	 * getId
	 *
	 * @return $id
	 */
	public function getId(): string
	{
		return $this->id;
	}
	/**
	 * getLabel
	 *
	 * @return $label
	 */
	public function getLabel(): string
	{
		return $this->label;
	}
	/**
	 * getMaxLength
	 *
	 * @return $maxLength
	 */
	public function getMaxLength(): int
	{
		return $this->maxLength;
	}
	/**
	 * getMaxValue
	 *
	 * @return $maxValue
	 */
	public function getMaxValue(): float
	{
		return $this->maxValue;
	}
	/**
	 * getMinLength
	 *
	 * @return $minLength
	 */
	public function getMinLength(): int
	{
		return $this->minLength;
	}
	/**
	 * getMinValue
	 *
	 * @return $minValue
	 */
	public function getMinValue(): float
	{
		return $this->minValue;
	}
	/**
	 * getName
	 *
	 * @return $name
	 */
	public function getName(): string
	{
		return $this->name;
	}
	/**
	 * getOnInsert
	 *
	 * @return $onInsert
	 */
	public function getOnInsert(): ?string
	{
		return $this->onInsert;
	}
	/**
	 * getOnUpdate
	 *
	 * @return $onUpdate
	 */
	public function getOnUpdate(): ?string
	{
		return $this->onUpdate;
	}
	/**
	 * getPrecision
	 *
	 * @return $precision
	 */
	public function getPrecision(): ?int
	{
		return $this->precision;
	}
	/**
	 * getPrimary
	 *
	 * @return $primary
	 */
	public function getPrimary(): bool
	{
		return $this->primary;
	}
	/**
	 * getQuery
	 *
	 * @return $query
	 */
	public function getQuery(): bool
	{
		return $this->query;
	}
	/**
	 * getRequired
	 *
	 * @return $required
	 */
	public function getRequired(): bool
	{
		return $this->required;
	}
	/**
	 * getRestrict
	 *
	 * @return $restrict
	 */
	public function getRestrict(): array
	{
		return $this->restrict;
	}
	/**
	 * getScale
	 *
	 * @return $scale
	 */
	public function getScale(): ?int
	{
		return $this->scale;
	}
	/**
	 * getSource
	 *
	 * @return $source
	 */
	public function getSource(): ?string
	{
		return $this->source;
	}
	/**
	 * getTransform
	 *
	 * @return $transform
	 */
	public function getTransform()
	{
		return $this->transform;
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
	 * getUnique
	 *
	 * @return $unique
	 */
	public function getUnique()
	{
		return $this->unique;
	}
	/**
	 * getUnsigned
	 *
	 * @return $unsigned
	 */
	public function getUnsigned()
	{
		return $this->unsigned;
	}
	/**
	 * setAlias
	 *
	 * @param $alias
	 */
	public function setAlias(?string $alias)
	{
		$this->alias = $alias;
		return $this;
	}
	/**
	 * setAuto
	 *
	 * @param $auto
	 */
	public function setAuto($auto)
	{
		$this->auto = $auto;
		return $this;
	}
	/**
	 * setCharset
	 *
	 * @param $charset
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
		return $this;
	}
	/**
	 * setCollation
	 *
	 * @param $collation
	 */
	public function setCollation($collation)
	{
		$this->collation = $collation;
		return $this;
	}
	/**
	 * setDefault
	 *
	 * @param $default
	 */
	public function setDefault($default)
	{
		$this->default = $default;
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
	 * setId
	 *
	 * @param $id
	 */
	public function setId(string $id)
	{
		$this->id = $id;
		return $this;
	}
	/**
	 * setLabel
	 *
	 * @param $label
	 */
	public function setLabel($label)
	{
		$this->label = $label;
		return $this;
	}
	/**
	 * setMaxLength
	 *
	 * @param $maxLength
	 */
	public function setMaxLength(?int $maxLength)
	{
		if (!is_null($maxLength)) {
			$this->maxLength = $maxLength;
		}

		return $this;
	}
	/**
	 * setMaxValue
	 *
	 * @param $maxValue
	 */
	public function setMaxValue($maxValue)
	{
		$this->maxValue = $maxValue;
		return $this;
	}
	/**
	 * setMinLength
	 *
	 * @param $minLength
	 */
	public function setMinLength($minLength)
	{
		$this->minLength = $minLength;
		return $this;
	}
	/**
	 * setMinValue
	 *
	 * @param $minValue
	 */
	public function setMinValue($minValue)
	{
		$this->minValue = $minValue;
		return $this;
	}
	/**
	 * setOnInsert
	 *
	 * @param $onInsert
	 */
	public function setOnInsert(string $onInsert)
	{
		$this->onInsert = $onInsert;
		return $this;
	}
	/**
	 * setOnUpdate
	 *
	 * @param $onUpdate
	 */
	public function setOnUpdate(string $onUpdate)
	{
		$this->onUpdate = $onUpdate;
		return $this;
	}
	/**
	 * setName
	 *
	 * @param $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
		return $this;
	}
	/**
	 * setPrecision
	 *
	 * @param $precision
	 */
	public function setPrecision(?int $precision)
	{
		$this->precision = $precision;
		return $this;
	}
	/**
	 * setPrimary
	 *
	 * @param $primary
	 */
	public function setPrimary(?bool $primary)
	{
		$this->primary = $primary;
		return $this;
	}
	/**
	 * setQuery
	 *
	 * @param $query
	 */
	public function setQuery(?bool $query)
	{
		$this->query = $query;
		return $this;
	}
	/**
	 * setRequired
	 *
	 * @param $required
	 */
	public function setRequired(?bool $required)
	{
		$this->required = $required;
		return $this;
	}
	/**
	 * setRestrict
	 *
	 * @param $restrict
	 */
	public function setRestrict(?array $restrict)
	{
		$this->restrict = $restrict;
		return $this;
	}
	/**
	 * setScale
	 *
	 * @param $scale
	 */
	public function setScale($scale)
	{
		$this->scale = $scale;
		return $this;
	}
	/**
	 * setSource
	 *
	 * @param $source
	 */
	public function setSource($source)
	{
		$this->source = $source;
		return $this;
	}
	/**
	 * setTransform
	 *
	 * @param $transform
	 */
	public function setTransform($transform)
	{
		$this->transform = $transform;
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
	 * setUnique
	 *
	 * @param $unique
	 */
	public function setUnique($unique)
	{
		$this->unique = $unique;
		return $this;
	}
	/**
	 * setUnsigned
	 *
	 * @param $unsigned
	 */
	public function setUnsigned($unsigned)
	{
		$this->unsigned = $unsigned;
		return $this;
	}
}

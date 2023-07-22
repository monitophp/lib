<?php
/**
 * Database\Model\Field.
 *
 * @version 1.0.1
 */

namespace MonitoLib\Database\Model;

class Field
{
    private $id;
    private $name;
    private $auto = false;
    private $source;
    private $type = 'string';
    private $format;
    private $charset = 'utf8';
    private $collation = 'utf8_general_ci';
    private $default;
    private $label = '';
    private $maxLength = 0;
    private $minLength = 0;
    private $maxValue = 0;
    private $minValue = 0;
    private $precision;
    private $scale;
    private $primary = false;
    private $required = false;
    private $transform;
    private $unique = false;
    private $unsigned = false;

    public function getAuto(): bool
    {
        return $this->auto;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function getCollation()
    {
        return $this->collation;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    public function getMaxValue(): float
    {
        return $this->maxValue;
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    public function getMinValue(): float
    {
        return $this->minValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function getPrimary(): bool
    {
        return $this->primary;
    }

    public function getRequired(): bool
    {
        return $this->required;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getTransform()
    {
        return $this->transform;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getUnique()
    {
        return $this->unique;
    }

    public function getUnsigned()
    {
        return $this->unsigned;
    }

    public function setAuto($auto)
    {
        $this->auto = $auto;

        return $this;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    public function setCollation($collation)
    {
        $this->collation = $collation;

        return $this;
    }

    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    public function setId(string $id)
    {
        $this->id = $id;

        return $this;
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function setMaxLength($maxLength)
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    public function setMaxValue($maxValue)
    {
        $this->maxValue = $maxValue;

        return $this;
    }

    public function setMinLength($minLength)
    {
        $this->minLength = $minLength;

        return $this;
    }

    public function setMinValue($minValue)
    {
        $this->minValue = $minValue;

        return $this;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function setPrecision($precision)
    {
        $this->precision = $precision;

        return $this;
    }

    public function setPrimary($primary)
    {
        $this->primary = $primary;

        return $this;
    }

    public function setRequired($required)
    {
        $this->required = $required;

        return $this;
    }

    public function setScale($scale)
    {
        $this->scale = $scale;

        return $this;
    }

    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    public function setTransform($transform)
    {
        $this->transform = $transform;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setUnique($unique)
    {
        $this->unique = $unique;

        return $this;
    }

    public function setUnsigned($unsigned)
    {
        $this->unsigned = $unsigned;

        return $this;
    }
}

<?php

namespace MonitoLib\Database\Query;

use \MonitoLib\Database\Query;

class Options
{
	const VERSION = '1.0.0';
	/**
	 * 1.0.0 - 2021-07-08
	 * Initial release
	 */

	private $fixedQuery = false;
	private $checkNull  = false;
	private $rawQuery   = false;
	private $operator   = 'AND';
	private $startGroup = false;
	private $endGroup   = false;

	public function __construct(int $options)
	{
		$this->parse($options);
	}
	/**
	 * getFixedQuery
	 *
	 * @return $fixedQuery
	 */
	public function isFixed()
	{
		return $this->fixedQuery;
	}
	/**
	 * getCheckNull
	 *
	 * @return $checkNull
	 */
	public function checkNull()
	{
		return $this->checkNull;
	}
	/**
	 * getRawQuery
	 *
	 * @return $rawQuery
	 */
	public function isRaw()
	{
		return $this->rawQuery;
	}
	/**
	 * getOperator
	 *
	 * @return $operator
	 */
	public function getOperator()
	{
		return $this->operator;
	}
	/**
	 * getStartGroup
	 *
	 * @return $startGroup
	 */
	public function startGroup(): bool
	{
		return $this->startGroup;
	}
	/**
	 * getEndGroup
	 *
	 * @return $endGroup
	 */
	public function endGroup(): bool
	{
		return $this->endGroup;
	}
	private function parse(int $options): void
	{
		if ($options > 0) {
			$this->fixedQuery = ($options & Query::FIXED)       === Query::FIXED;
			$this->checkNull  = ($options & Query::CHECK_NULL)  === Query::CHECK_NULL;
			$this->rawQuery   = ($options & Query::RAW_QUERY)   === Query::RAW_QUERY;
			$this->operator   = ($options & Query::OR)          === Query::OR ? 'OR' : 'AND';
			$this->startGroup = ($options & Query::START_GROUP) === Query::START_GROUP;
			$this->endGroup   = ($options & Query::END_GROUP)   === Query::END_GROUP;
		}
	}
}

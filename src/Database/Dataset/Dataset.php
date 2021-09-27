<?php

namespace MonitoLib\Database\Dataset;

class Dataset implements \JsonSerializable
{
	const VERSION = '1.0.0';
	/**
	 * 1.0.0 - 2021-04-22
	 * Initial version
	 */

	private $data;
	private $pagination;
	private $query;

	public function __construct($data, Pagination $pagination)
	{
		$this->data       = $data;
		$this->pagination = $pagination;
	}
	public function __toString(): string
	{
		return '{"data": ' . (string)$this->data . ','
			. '"pagination": ' . (string)$this->pagination
			. '}';
		// return join(array_map(fn($e) => (string)$e, $this->data));
		// return json_encode($this->jsonSerialize());
	}
	/**
	 * getData
	 *
	 * @return $data
	 */
	public function getData()
	{
		return $this->data;
	}
	/**
	 * getPagination
	 *
	 * @return $pagination
	 */
	public function getPagination()
	{
		return $this->pagination;
	}
	public function jsonSerialize()
	{
		return [
			'data'       => $this->data,
			'pagination' => $this->pagination,
		];
	}
}

<?php

namespace MonitoLib\Database\Dataset;

class Collection extends \ArrayIterator
{
    const VERSION = '1.0.0';
    /**
     * 1.0.0 - 2021-04-22
     * Initial version
     */

    public function __toString() : string
    {
		return '[' . join(',', array_map(fn($e) => (string)$e, (array)$this)) . ']';
    }
}

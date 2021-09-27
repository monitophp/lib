<?php

namespace MonitoLib\Traits;

trait ToString
{
    use \MonitoLib\Traits\DbClass;

    public function __toString(): string
    {
        $this->getDbClass();
        // $class = get_class($this);
        // \MonitoLib\Dev::ee($class);
        $properties = get_object_vars($this);

        // \MonitoLib\Dev::pre($properties);

        $properties = array_map(fn ($e) => is_object($e) ? (string)$e : $e, $properties);
        return json_encode($properties);
    }
}

<?php
namespace MonitoLib\Traits;

trait ToString
{
    public function __toString() : string
    {
        $properties = get_object_vars($this);
        $properties = array_map(fn($e) => is_object($e) ? (string)$e : $e, $properties);
        return json_encode($properties);
    }
}
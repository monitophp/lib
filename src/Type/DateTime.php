<?php
namespace MonitoLib\Type;

class DateTime extends \DateTime
{
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
    public function addMinutes(int $minutes) : self
    {
        $this->add(new \DateInterval("P{$minutes}M"));
        return $this;
    }
    public function subMinutes(int $minutes)
    {
        $this->sub(new \DateInterval("P{$minutes}M"));
    }
}
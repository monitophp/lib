<?php
namespace MonitoLib\Type;

class DateTime extends \DateTime
{
    public function __construct(?string $datetime = 'now', ?string $timezone = null)
    {
        parent::__construct($datetime);
        $timezone ??= date_default_timezone_get();
        $this->setTimezone(new \DateTimeZone($timezone));
    }
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }
    public function addMinutes(int $minutes) : self
    {
        $this->add(new \DateInterval("P{$minutes}M"));
        return $this;
    }
    public function getTimezone()
    {
        return '';
    }
    public function subMinutes(int $minutes)
    {
        $this->sub(new \DateInterval("P{$minutes}M"));
    }
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }
}
<?php

namespace Norm\Type;

class DateTime extends \DateTime implements \JsonKit\JsonSerializer
{
    public function localFormat($format)
    {
        return $this->tzFormat($format);
    }

    public function tzFormat($format, $tz = null)
    {
        if (isset($tz)) {
            $this->setTimezone(new \DateTimeZone($tz));
        }
        return $this->format($format);
    }

    public function jsonSerialize()
    {
        return $this->format('c');
    }

    public function __toString()
    {
        return $this->format('c');
    }
}

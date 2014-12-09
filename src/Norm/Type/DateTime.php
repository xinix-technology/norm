<?php

namespace Norm\Type;

use Norm\Norm;

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
        if ($tz = Norm::options('tz')) {
            $this->setTimezone(new \DateTimeZone($tz));
        }
        return $this->format('c');
    }

    public function __toString()
    {
        if ($tz = Norm::options('tz')) {
            $this->setTimezone(new \DateTimeZone($tz));
        }
        return $this->format('c');
    }
}

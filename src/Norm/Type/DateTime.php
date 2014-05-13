<?php

namespace Norm\Type;

class DateTime extends \DateTime implements \JsonKit\JsonSerializer
{

    public function jsonSerialize()
    {
        return $this->format('c');
    }

    public function __toString()
    {
        return $this->format('c');
    }

    public function normalize()
    {
        return (string) $this;
    }
}

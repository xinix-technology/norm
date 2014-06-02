<?php

namespace Norm\Type;

class Secret implements \JsonKit\JsonSerializer
{
    protected $value;

    public function __construct($val)
    {
        $this->value = $val;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function jsonSerialize()
    {
        return '';
    }

    public function marshall()
    {
        return $this->value;
    }
}

<?php

namespace Norm\Type;

class NormArray extends Collection
{
    public function __construct($attributes = null)
    {
        parent::__construct($attributes);
        $this->attributes = array_values($this->attributes);
    }

    public function add($o)
    {
        $this->attributes[] = $o;
    }

    public function has($o)
    {
        return in_array($o, $this->attributes);
    }

    public function offsetSet($key, $value)
    {
        if (!is_int($key)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$key] = $value;
        }
    }
}

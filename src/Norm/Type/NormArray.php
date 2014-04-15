<?php

namespace Norm\Type;

class NormArray implements \JsonKit\JsonSerializer, \ArrayAccess, \Iterator
{
    protected $attributes = array();

    public function __construct($attributes = null)
    {
        if ($attributes) {
            if ($attributes instanceof NormArray) {
                $attributes = $attributes->toArray();
            }
            $this->attributes = $attributes;
        }

    }

    public function jsonSerialize()
    {
        return $this->attributes;
    }

    public function offsetGet($key)
    {
        return $this->attributes[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function offsetExists($key)
    {
        return isset($this->attributes[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->attributes[$key]);
    }

    public function add($o)
    {
        $this->attributes[] = $o;
    }

    public function has($o)
    {
        return in_array($o, $this->attributes);
    }

    public function current()
    {
        return current($this->attributes);
    }

    public function next()
    {
        return next($this->attributes);
    }

    public function key()
    {
        return key($this->attributes);
    }

    public function valid()
    {
        return $this->current();
    }

    public function rewind()
    {
        return reset($this->attributes);
    }

    public function toArray()
    {
        return $this->attributes;
    }
}

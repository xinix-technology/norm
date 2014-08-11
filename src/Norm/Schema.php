<?php

namespace Norm;

class Schema
{
    protected $schemes;

    public function __construct($schemes = array())
    {
        $this->schemes = $schemes;
    }

    public function get($name)
    {
        return isset($this->schemes[$name]) ? $this->schemes[$name] : null;
    }

    public function toArray()
    {
        return $this->schemes;
    }
}

<?php

namespace Norm\Type;

class NormArray extends Collection
{
    public function __construct($attributes = null)
    {
        parent::__construct($attributes);
        $this->attributes = array_values($this->attributes);
    }
}

<?php

namespace Norm\Schema;

class Float extends Field
{
    public function prepare($value)
    {
        return (double) $value;
    }
}

<?php

namespace Norm\Schema;

class Integer extends Field
{
    public function prepare($value)
    {
        return (int) $value;
    }
}

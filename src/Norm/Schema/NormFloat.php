<?php

namespace Norm\Schema;

class NormFloat extends Field
{
    public function prepare($value)
    {
        return (double) $value;
    }
}

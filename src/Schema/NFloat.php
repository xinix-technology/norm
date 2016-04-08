<?php

namespace Norm\Schema;

class NFloat extends NField
{
    public function prepare($value)
    {
        return (double) $value;
    }
}

<?php

namespace Norm\Schema;

class NInteger extends NField
{
    public function prepare($value)
    {
        return (int) $value;
    }
}

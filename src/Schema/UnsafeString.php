<?php

namespace Norm\Schema;

class UnsafeString extends String
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

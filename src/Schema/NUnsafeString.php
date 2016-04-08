<?php

namespace Norm\Schema;

class NUnsafeString extends String
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

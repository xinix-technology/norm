<?php

namespace Norm\Schema;

class NUnsafeString extends NString
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

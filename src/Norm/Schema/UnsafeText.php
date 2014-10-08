<?php

namespace Norm\Schema;

class UnsafeText extends Text
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

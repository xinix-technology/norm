<?php

namespace Norm\Schema;

class NUnsafeText extends Text
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

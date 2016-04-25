<?php

namespace Norm\Schema;

class NUnsafeText extends NText
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

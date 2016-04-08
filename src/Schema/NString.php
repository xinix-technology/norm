<?php

namespace Norm\Schema;

class NString extends NField
{
    public function prepare($value)
    {
        return utf8_encode(filter_var($value, FILTER_SANITIZE_STRING));
    }
}

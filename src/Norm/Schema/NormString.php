<?php

namespace Norm\Schema;

class NormString extends Field
{
    public function prepare($value)
    {
        return utf8_encode(filter_var($value, FILTER_SANITIZE_STRING));
    }
}

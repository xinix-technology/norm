<?php

namespace Norm\Schema;

class NString extends NField
{
    protected function execPrepare($value)
    {
        return utf8_encode(filter_var($value, FILTER_SANITIZE_STRING));
    }
}

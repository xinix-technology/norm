<?php

namespace Norm\Schema;

class NUnsafeString extends NString
{
    public function execPrepare($value)
    {
        return utf8_encode($value);
    }
}

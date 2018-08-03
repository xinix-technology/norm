<?php

namespace Norm\Schema;

class NUnsafeText extends NText
{
    public function execPrepare($value)
    {
        return utf8_encode($value);
    }
}

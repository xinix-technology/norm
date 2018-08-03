<?php

namespace Norm\Schema;

class NFloat extends NField
{
    public function execPrepare($value)
    {
        return (double) $value;
    }
}

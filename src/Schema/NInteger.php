<?php

namespace Norm\Schema;

class NInteger extends NField
{
    public function execPrepare($value)
    {
        return (int) $value;
    }
}

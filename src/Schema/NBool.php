<?php

namespace Norm\Schema;

class NBool extends NField
{
    public function execPrepare($value)
    {
        // support empty string or null as null value
        if (null !== $value && '' !== $value) {
            return (bool) $value;
        }
    }
}

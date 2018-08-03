<?php

namespace Norm\Schema;

use Norm\Type\NormObject as TypeObject;

class NObject extends NField
{
    public function execPrepare($value)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof TypeObject) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new TypeObject($value);
    }
}

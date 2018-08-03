<?php

namespace Norm\Schema;

use Norm\Type\ArrayList;

class NList extends NField
{
    public function execPrepare($value)
    {
        if (empty($value)) {
            return new ArrayList();
        } elseif ($value instanceof ArrayList) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new ArrayList($value);
    }
}

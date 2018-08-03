<?php

namespace Norm\Schema;

use Norm\Type\Secret as Secret;

class NPassword extends NField
{
    public function execPrepare($value)
    {
        if ($value instanceof Secret) {
            return $value;
        } elseif (empty($value)) {
            return null;
        } else {
            return new Secret($value);
        }
    }
}

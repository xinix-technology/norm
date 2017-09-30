<?php

namespace Norm\Schema;

class NormInteger extends Field
{
    public function prepare($value)
    {
        return (int) $value;
    }
}

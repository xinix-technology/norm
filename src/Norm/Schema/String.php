<?php

namespace Norm\Schema;

class String extends Field
{
    public function prepare($value)
    {
        return utf8_encode($value);
    }
}

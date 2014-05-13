<?php

namespace Norm\Schema;

// FIXME unimplemented yet!
class Object extends Field
{
    public function prepare($value)
    {

        if (is_string($value)) {
            $value = json_decode($value);
        }

        return new \Norm\Type\Object($value);
    }
}

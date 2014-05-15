<?php

namespace Norm\Type;

class Object extends Collection
{
    public function toObject()
    {
        $obj = new \stdClass();
        if (!empty($this->attributes)) {
            foreach ($this->attributes as $key => $value) {
                $obj->$key = $value;
            }
        }
        return $obj;
    }

    public function has($o)
    {
        $attrs = array_values($this->attributes);
        return in_array($o, $attrs);
    }
}

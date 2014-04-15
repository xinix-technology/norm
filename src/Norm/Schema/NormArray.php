<?php

namespace Norm\Schema;

class NormArray extends Field
{

    public function prepare($value)
    {

        if (empty($value)) {
            return new \Norm\Type\NormArray();
        }

        if (is_string($value)) {
            $value = json_decode($value);
        }

        return new \Norm\Type\NormArray($value);
    }

    public function input($value, $entry = null)
    {
        $texts = array();
        if ($value) {
            if ($value instanceof \Norm\Type\NormArray) {
                $value = $value->toArray();
            }
            $value = implode(',', $value);
        }


        return parent::input($value, $entry);
    }
}

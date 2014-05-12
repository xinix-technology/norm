<?php

namespace Norm\Schema;

use \Norm\Type\Object;

class NormArray extends Field
{

    public function prepare($value)
    {

        if (empty($value)) {
            return new Object();
        } elseif ($value instanceof Object) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value);
        }

        return new Object($value);
    }

    public function presetReadonly($value, $entry = null)
    {
        $value = $this->prepare($value);
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            // if (substr(phpversion(), 0, 3) === '5.3') {
            //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            // } else {
            $value = json_encode($value->toArray());
            // }
        }

        return parent::presetReadonly($value, $entry);
    }

    public function presetInput($value, $entry = null)
    {
        $value = $this->prepare($value);
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            // if (substr(phpversion(), 0, 3) === '5.3') {
            //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            // } else {
            $value = json_encode($value->toArray());
            // }
        }

        return '<textarea name="'.$this['name'].'">'.$value.'</textarea>';
    }
}

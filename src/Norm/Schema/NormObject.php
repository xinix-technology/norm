<?php

namespace Norm\Schema;

use Norm\Type\NObject as TypeObject;

class NormObject extends Field
{

    public function prepare($value)
    {

        if (empty($value)) {
            return new TypeObject();
        } elseif ($value instanceof TypeObject) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new TypeObject($value);
    }

    public function formatReadonly($value, $entry = null)
    {
        $value = $this->prepare($value);
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            // if (substr(phpversion(), 0, 3) === '5.3') {
            //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            // } else {
            $value = json_encode($value->toObject());
            // }
        }

        return parent::formatReadonly($value, $entry);
    }

    public function formatInput($value, $entry = null)
    {
        $value = $this->prepare($value);
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            // if (substr(phpversion(), 0, 3) === '5.3') {
            //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            // } else {
            $value = json_encode($value->toObject());
            // }
        }

        return '<textarea name="'.$this['name'].'">'.$value.'</textarea>';
    }
}

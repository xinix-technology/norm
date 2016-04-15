<?php

namespace Norm\Schema;

use Norm\Type\Object as TypeObject;

class NObject extends NField
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

    protected function formatReadonly($value, $model = null)
    {
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            // if (substr(phpversion(), 0, 3) === '5.3') {
            //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            // } else {
            $value = json_encode($value->toObject());
            // }
        }

        return parent::formatReadonly($value, $model);
    }

    protected function formatInput($value, $model = null)
    {
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

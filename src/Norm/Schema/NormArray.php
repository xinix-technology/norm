<?php

namespace Norm\Schema;

use Norm\Type\NormArray as TypeArray;

class NormArray extends Field
{

    public function prepare($value)
    {

        if (empty($value)) {
            return new TypeArray();
        } elseif ($value instanceof TypeArray) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new TypeArray($value);
    }

    public function formatPlain($value, $entry = null)
    {
        $value = $this->prepare($value);
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            if (substr(phpversion(), 0, 3) > '5.3') {
                $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
            } else {
                $value = json_encode($value->toArray());
            }
        }

        return $value;
    }

    public function formatInput($value, $entry = null)
    {
        return '<textarea name="'.$this['name'].'">'.$this->formatPlain($value, $entry).'</textarea>';
    }

    public function formatReadonly($value, $entry = null)
    {
        return "<span class=\"field\"><pre style=\"margin:0\">".($this->formatPlain($value, $entry) ?: '&nbsp;')."</pre></span>";
    }
}

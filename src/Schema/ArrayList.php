<?php

namespace Norm\Schema;

use Norm\Type\ArrayList as TypeArray;

class ArrayList extends Field
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

    public function formatPlain($value, $model = null)
    {
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

    public function formatInput($value, $model = null)
    {
        return $this->render('_schema/array/input', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    public function formatReadonly($value, $model = null)
    {
        $html = "<span class=\"field\">\n";
        if (!empty($value)) {
            foreach ($value as $key => $v) {
                $html .= '<code>'.$v."</code>\n";
            }
        }
        $html .= "</span>\n";
        return $html;
    }
}

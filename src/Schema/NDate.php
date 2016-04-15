<?php

namespace Norm\Schema;

use DateTimeZone;

class NDate extends DateTime
{

    protected function formatInput($value, $model = null)
    {
        if ($value) {
            $value->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        }

        return '<input type="date" name="'.$this['name'].'" value="'.($value ? $value->format('Y-m-d') : '').
            '" placeholder="'.$this['label'].
            '" autocomplete="off" />';
    }

    protected function formatPlain($value, $model = null)
    {
        if ($value) {
            $value->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        }

        return ($value ? $value->format('Y-m-d') : null);
    }

    protected function formatReadonly($value, $model = null)
    {
        if ($value) {
            $value->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        }

        return '<span class="field">'.($value ? $value->format('m/d/Y') : '&nbsp;').'</span>';
    }

    // DEPRECATED replaced by Field::render
    // public function cell($value, $model = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $model);
    //     }
    //     return $value->format('Y-m-d');
    // }
}

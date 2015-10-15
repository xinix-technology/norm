<?php

namespace Norm\Schema;

class Date extends DateTime
{

    public function formatInput($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<input type="date" name="'.$this['name'].'" value="'.($value ? $value->format('Y-m-d') : '').
            '" placeholder="'.$this['label'].
            '" autocomplete="off" />';
    }

    public function formatPlain($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return ($value ? $value->format('Y-m-d') : null);
    }

    public function formatReadonly($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<span class="field">'.($value ? $value->format('m/d/Y') : '&nbsp;').'</span>';
    }

    // DEPRECATED replaced by Field::render
    // public function cell($value, $entry = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $entry);
    //     }
    //     return $value->format('Y-m-d');
    // }
}

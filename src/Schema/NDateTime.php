<?php

namespace Norm\Schema;

use DateTimeZone;
use DateTime;
use Norm\Type\DateTime as TypeDateTime;

class NDateTime extends NField
{
    public function prepare($value)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof TypeDateTime) {
            return $value;
        } elseif ($value instanceof DateTime) {
            $t = $value->format('c');
        } elseif (is_string($value)) {
            $t = date('c', strtotime($value));
        } else {
            $t = date('c', (int) $value);
        }
        return new TypeDateTime($this->repository, $t);
    }

    public function formatInput($value, $model = null)
    {
        if ($value) {
            $value->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        }

        return '<input type="datetime-local" name="'.$this['name'].'" value="'.
            ($value ? $value->format("Y-m-d\TH:i") : '').'" placeholder="'.
            $this['label'].'" autocomplete="off" />';
    }

    public function formatReadonly($value, $model = null)
    {
        if ($value) {
            $value->setTimeZone(new DateTimeZone(date_default_timezone_get()));
        }

        return '<span class="field">'.($value ? $value->format('c') : '&nbsp;').'</span>';
    }

    // DEPRECATED replaced by Field::render
    // public function cell($value, $model = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $model);
    //     }
    //     return $value->format('Y-m-d H:i:s a');
    // }
}

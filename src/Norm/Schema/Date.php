<?php

namespace Norm\Schema;

class Date extends DateTime
{

    public function input($value, $entry = null)
    {
        if (is_string($value)) {
            $value = new \Norm\Type\DateTime(date('c', strtotime($value)));
        }

        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        if ($this['readonly']) {
            return '<span class="field">'.(($value) ? $value->format('Y-m-d') : '').'</span>';
        }

        if ($format = $this['inputFormat']) {
            return $format($value, $entry);
        }

        if ($value) {
            $value = $value->format('Y-m-d');
        }

        return '<input type="date" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].
            '" autocomplete="off" />';
    }

    public function cell($value, $entry = null)
    {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry);
        }
        return $value->format('Y-m-d');
    }
}

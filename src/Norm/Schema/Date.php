<?php

namespace Norm\Schema;

class Date extends DateTime {

    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return '<span class="field">'.date('Y-m-d', strtotime($value)).'</span>';
        }
        if ($format = $this['inputFormat']) {
            return $format($value, $entry);
        }
        if ($value) {
            $value = date('Y-m-d', strtotime($value));
        }

        return '<input type="date" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry);
        }
        return date('Y-m-d', strtotime($value));
    }
}
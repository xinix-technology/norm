<?php

namespace Norm\Schema;

class DateTime extends Field {
    public function prepare($value) {
        if ($value instanceof \DateTime) {
            return $value->format('c');
        } elseif (is_string($value)) {
            return date('c', strtotime($value));
        }
        return date('c', (int) $value);
    }

    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return '<span class="field">'.$value.'</span>';
        }
        if ($format = $this['inputFormat']) {
            return $format($value, $entry);
        }
        return '<input type="date" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }
}
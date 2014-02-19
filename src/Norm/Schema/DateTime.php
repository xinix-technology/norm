<?php

namespace Norm\Schema;

class DateTime extends Field {
    public function prepare($value) {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTime) {
            $t = $value->format('c');
        } elseif (is_string($value)) {
            $t = date('c', strtotime($value));
        } else {
            $t = date('c', (int) $value);
        }
        return new \Norm\Type\DateTime($t);
    }

    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return '<span class="field">'.$value.'</span>';
        }
        if ($format = $this['inputFormat']) {
            return $format($value, $entry);
        }
        $value = date('c', strtotime($value));
        return '<input type="datetime-local" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }
}
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
        if ($value) {
            $value = $value->format("Y-m-d\TH:i");
        }
        return '<input type="datetime-local" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry);
        }
        return $value->format('Y-m-d H:i:s a');
    }
}

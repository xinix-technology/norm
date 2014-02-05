<?php

namespace Norm\Schema;

class Text extends String {
    public function input($value, $entry = NULL) {
        return '<textarea name="'.$this['name'].'">'.(@$value).'</textarea>';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry);
        }
        return substr($value, 0, 75).'...';
    }
}
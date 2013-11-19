<?php

namespace Norm\Schema;

class Boolean extends Field {
    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return parent::input($value == 1 ? 'True' : 'False', $entry);
        }

        return '
            <select name="'.$this['name'].'">
                <option value="0" '.(!$value ? 'selected' : '').'>False</option>
                <option value="1" '.($value ? 'selected' : '').'>True</option>
            </select>
        ';
    }

    public function cell($value, $entry = NULL) {
        return $value == 1 ? 'True' : 'False';
    }
}
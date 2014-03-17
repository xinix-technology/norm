<?php

namespace Norm\Schema;

class Boolean extends Field {
    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return parent::input($value ? 'True' : 'False', $entry);
        }

        return '
            <select name="'.$this['name'].'">
                <option value="0" '.(!$value ? 'selected' : '').'>False</option>
                <option value="1" '.($value ? 'selected' : '').'>True</option>
            </select>
        ';
    }

    public function cell($value, $entry = NULL) {
        return $value ? 'True' : 'False';
    }

    public function prepare($value) {
        return (int) $value;
    }
}

<?php

namespace Norm\Schema;

class Boolean extends Field {
    public function input($value, $entry = NULL) {
        if ($this['readOnly']) {
            return parent::input(($value == 1) ? 'True' : 'False', $entry);
        }

        return '
            <select name="'.$this['name'].'">
                <option value="0">False</option>
                <option value="1">True</option>
            </select>
        ';
    }

    public function cell($value, $entry = NULL) {
        return ($value == 1) ? 'True' : 'False';
    }
}
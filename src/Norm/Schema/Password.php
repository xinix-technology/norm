<?php

namespace Norm\Schema;

class Password extends Field {
    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            return '<span class="field">*hidden*</span>';
        }

        return '
            <div class="row">
                <input class="span-6" type="password" name="'.$this['name'].'" value="" placeholder="Password" autocomplete="off" />
                <input class="span-6" type="password" name="'.$this['name'].'_confirmation" value="" placeholder="Password confirmation" autocomplete="off" />
            </div>
        ';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat')) {
            return parent::cell($value, $entry);
        }
        return '*hidden*';
    }

    // public function prepare($value) {
    //     return $value;
    // }

    public function toJSON($value) {
        return '';
    }
}
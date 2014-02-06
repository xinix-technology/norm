<?php

namespace Norm\Schema;

class Object extends Field {
    public function prepare($value) {
        return json_decode($value);
    }

    public function input($value, $entry = NULL) {
        return '<textarea name="'.$this['name'].'">'.(@$value).'</textarea>';
    }
}
<?php

namespace Norm\Schema;

class Text extends Field {
    public function input($value, $entry = NULL) {
        return '<textarea name="'.$this['name'].'">'.(@$value).'</textarea>';
    }
}
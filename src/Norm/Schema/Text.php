<?php

namespace Norm\Schema;

class Text extends String {
    public function input($value, $entry = NULL) {
        return '<textarea name="'.$this['name'].'">'.(@$value).'</textarea>';
    }
}
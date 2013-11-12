<?php

namespace Norm\Schema;

class Password extends Field {
    public function input($value, $entry = NULL) {
        return '
            <div class="row">
                <input class="span-6" type="password" name="'.$this['name'].'" value="" placeholder="Password" autocomplete="off" />
                <input class="span-6" type="password" name="'.$this['name'].'_retype" value="" placeholder="Retype password" autocomplete="off" />
            </div>
        ';
    }

    public function cell($value, $entry = NULL) {
        return '*hidden*';
    }
}
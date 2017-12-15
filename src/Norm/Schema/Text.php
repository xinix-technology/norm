<?php

namespace Norm\Schema;

class Text extends String
{

    public function formatReadonly($value, $entry = null)
    {
        return parent::formatReadonly(nl2br($value), $entry);
    }

    public function formatInput($value, $entry = null)
    {
        return '<textarea class="'.$this->inputClass().'" '. $this->inputAttributes() .' name="'.$this['name'].'" placeholder="'.$this['label'].'">'.$value.'</textarea>';
    }
}

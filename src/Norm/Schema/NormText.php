<?php

namespace Norm\Schema;

class NormText extends String
{

    public function formatReadonly($value, $entry = null)
    {
        return parent::formatReadonly(nl2br($value), $entry);
    }

    public function formatInput($value, $entry = null)
    {
        return '<textarea name="'.$this['name'].'" placeholder="'.$this['label'].'">'.$value.'</textarea>';
    }
}

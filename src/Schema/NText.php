<?php

namespace Norm\Schema;

class NText extends String
{

    public function formatReadonly($value, $model = null)
    {
        return parent::formatReadonly(nl2br($value), $model);
    }

    public function formatInput($value, $model = null)
    {
        return '<textarea name="'.$this['name'].'" placeholder="'.$this['label'].'">'.$value.'</textarea>';
    }
}

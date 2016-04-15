<?php

namespace Norm\Schema;

class NText extends String
{

    protected function formatReadonly($value, $model = null)
    {
        return parent::formatReadonly(nl2br($value), $model);
    }

    protected function formatInput($value, $model = null)
    {
        return '<textarea name="'.$this['name'].'" placeholder="'.$this['label'].'">'.$value.'</textarea>';
    }
}

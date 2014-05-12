<?php

namespace Norm\Schema;

class Text extends String
{

    public function presetReadonly($value, $entry = null)
    {
        return parent::presetReadonly(nl2br($value), $entry);
    }

    public function presetInput($value, $entry = null)
    {
        return '<textarea name="'.$this['name'].'">'.$value.'</textarea>';
    }

    // public function cell($value, $entry = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $entry);
    //     }
    //     return substr($value, 0, 75).'...';
    // }
}

<?php

namespace Norm\Schema;

class Password extends Field
{
    public function toJSON($value)
    {
        return '';
    }

    public function presetPlain($value, $entry = null)
    {
        return '';
    }

    public function presetInput($value, $entry = null)
    {
        return '
            <div class="row">
                <input class="span-6" type="password" name="'.$this['name'].
                '" value="" placeholder="Password" autocomplete="off" /><input class="span-6" type="password" name="'.
                $this['name'].'_confirmation" value="" placeholder="Password confirmation" autocomplete="off" />
            </div>
        ';
    }

    public function presetReadonly($value, $entry = null)
    {
        return '<span class="field">*hidden*</span>';
    }

    public function cell($value, $entry = null)
    {
        if ($this->has('cellFormat')) {
            return parent::cell($value, $entry);
        }
        return '*hidden*';
    }
}

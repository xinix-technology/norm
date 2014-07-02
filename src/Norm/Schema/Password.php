<?php

namespace Norm\Schema;

use Norm\Type\Secret as Secret;

class Password extends Field
{
    public function toJSON($value)
    {
        return '';
    }

    public function formatPlain($value, $entry = null)
    {
        return '';
    }

    public function formatInput($value, $entry = null)
    {
        return '
            <div class="row">
                <input class="span-6" type="password" name="'.$this['name'].
                '" value="" placeholder="Password" autocomplete="off" /><input class="span-6" type="password" name="'.
                $this['name'].'_confirmation" value="" placeholder="Password confirmation" autocomplete="off" />
            </div>
        ';
    }

    public function formatReadonly($value, $entry = null)
    {
        return '<span class="field">*hidden*</span>';
    }

    public function prepare($value)
    {
        if ($value instanceof Secret) {
            return $value;
        } else {
            return new Secret($value);
        }
    }

    // public function cell($value, $entry = null)
    // {
    //     if ($this->has('cellFormat')) {
    //         return parent::cell($value, $entry);
    //     }
    //     return '*hidden*';
    // }
}

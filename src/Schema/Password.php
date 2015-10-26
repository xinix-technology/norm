<?php

namespace Norm\Schema;

use Norm\Type\Secret as Secret;

class Password extends Field
{
    public function toJSON($value)
    {
        return null;
    }

    public function formatPlain($value, $model = null)
    {
        return '';
    }

    public function formatInput($value, $model = null)
    {
        return '
            <div class="row">
                <input class="span-6" type="password" name="'.$this['name'].
                '" value="" placeholder="Password" autocomplete="off" /><input class="span-6" type="password" name="'.
                $this['name'].'_confirmation" value="" placeholder="Password confirmation" autocomplete="off" />
            </div>
        ';
    }

    public function formatReadonly($value, $model = null)
    {
        return '<span class="field">*hidden*</span>';
    }

    public function prepare($value)
    {
        if ($value instanceof Secret) {
            return $value;
        } elseif (empty($value)) {
            return null;
        } else {
            return new Secret($value);
        }
    }
}

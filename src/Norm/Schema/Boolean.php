<?php

namespace Norm\Schema;

class Boolean extends Field
{
    public function prepare($value)
    {
        return (boolean) $value;
    }

    public function presetInput($value, $entry = null)
    {
        return '
            <select name="'.$this['name'].'">
                <option value="0" '.(!$value ? 'selected' : '').'>False</option>
                <option value="1" '.($value ? 'selected' : '').'>True</option>
            </select>
        ';
    }

    public function presetReadonly($value, $entry = null)
    {
        return $value ? 'True' : 'False';
    }
}

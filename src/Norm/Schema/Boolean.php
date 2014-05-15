<?php

namespace Norm\Schema;

class Boolean extends Field
{
    public function prepare($value)
    {
        return (boolean) $value;
    }

    public function formatInput($value, $entry = null)
    {
        return '
            <select name="'.$this['name'].'">
                <option value="0" '.(!$value ? 'selected' : '').'>False</option>
                <option value="1" '.($value ? 'selected' : '').'>True</option>
            </select>
        ';
    }

    public function formatPlain($value, $entry = null)
    {
        return $value ? 'True' : 'False';
    }

    public function formatReadonly($value, $entry = null)
    {
        return '<span class="field">'.$this->formatPlain($value, $entry).'</span>';
    }
}

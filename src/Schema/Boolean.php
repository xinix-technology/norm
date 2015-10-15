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
        return $this->render('_schema/boolean/input', array(
            'value' => $value,
            'entry' => $entry,
        ));
    }

    public function formatPlain($value, $entry = null)
    {
        return $value ? 'True' : 'False';
    }
}

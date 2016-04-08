<?php

namespace Norm\Schema;

class NBool extends NField
{
    public function prepare($value)
    {
        return (boolean) $value;
    }

    public function formatInput($value, $model = null)
    {
        return $this->render('_schema/nbool/input', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    public function formatPlain($value, $model = null)
    {
        return $value ? 'True' : 'False';
    }
}

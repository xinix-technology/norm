<?php

namespace Norm\Schema;

class NBool extends NField
{
    public function prepare($value)
    {
        // support empty string or null as null value
        if (null !== $value && '' !== $value) {
            return (boolean) $value;
        }
    }

    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/nbool/input', array(
            'value' => $value,
            'entry' => $model,
            'self' => $this,
        ));
    }

    protected function formatPlain($value, $model = null)
    {
        return $value ? 'True' : 'False';
    }
}

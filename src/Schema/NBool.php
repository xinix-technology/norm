<?php

namespace Norm\Schema;

class NBool extends NField
{
    public function prepare($value)
    {
        return (boolean) $value;
    }

    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/nbool/input', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    protected function formatPlain($value, $model = null)
    {
        return $value ? 'True' : 'False';
    }
}

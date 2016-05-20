<?php

namespace Norm\Schema;

class NFloat extends NField
{
    public function prepare($value)
    {
        return (double) $value;
    }

    protected function formatInput($value, $model = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }

        return $this->repository->render('__norm__/nfloat/input', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
    }
}

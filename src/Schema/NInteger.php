<?php

namespace Norm\Schema;

class NInteger extends NField
{
    public function prepare($value)
    {
        return (int) $value;
    }

    protected function formatInput($value, $model = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }

        return $this->repository->render('__norm__/ninteger/input', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
    }
}

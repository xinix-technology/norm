<?php

namespace Norm\Schema;

class NDate extends NDateTime
{
    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/ndate/input', [
            'value' => $value,
            'self' => $this,
        ]);
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->repository->render('__norm__/ndate/readonly', [
            'value' => $value,
            'self' => $this,
        ]);
    }

    protected function formatPlain($value, $model = null)
    {
        if (!empty($value)) {
            return $value->format('Y-m-d');
        }

        return '';
    }

    // DEPRECATED replaced by Field::render
    // public function cell($value, $model = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $model);
    //     }
    //     return $value->format('Y-m-d');
    // }
}

<?php

namespace Norm\Schema;

class NText extends NString
{
    protected function formatReadonly($value, $model = null)
    {
        return parent::formatReadonly(nl2br($value), $model);
    }

    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/ntext/input', [
            'self' => $this,
            'value' => $value,
        ]);
    }
}

<?php

namespace Norm\Schema;

class NToken extends NString
{
    protected function formatInput($value, $model = null)
    {
        return $this->render('__norm__/ntoken/input', [
            'value' => $value,
            'self' => $this,
        ]);
    }
}

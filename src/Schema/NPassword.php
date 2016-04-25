<?php

namespace Norm\Schema;

use Norm\Type\Secret as Secret;

class NPassword extends NField
{
    public function prepare($value)
    {
        if ($value instanceof Secret) {
            return $value;
        } elseif (empty($value)) {
            return null;
        } else {
            return new Secret($value);
        }
    }

    protected function formatJson($value, $model = null)
    {
        return null;
    }

    protected function formatPlain($value, $model = null)
    {
        return '';
    }

    protected function formatInput($value, $model = null)
    {
        return $this->render('__norm__/npassword/input', [
            'self' => $this,
        ]);
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->render('__norm__/npassword/readonly', [
            'self' => $this,
        ]);
    }
}

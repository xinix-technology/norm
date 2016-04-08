<?php

namespace Norm\Schema;

use Norm\Type\Secret as Secret;

class NPassword extends NField
{
    public function toJSON($value)
    {
        return null;
    }

    public function formatPlain($value, $model = null)
    {
        return '';
    }

    public function formatInput($value, $model = null)
    {
        return $this->render('__norm__/npassword/input', [
            'self' => $this,
        ]);
    }

    public function formatReadonly($value, $model = null)
    {
        return '<span class="field">*hidden*</span>';
    }

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
}

<?php

namespace Norm\Schema;

use Norm\Type\Object as TypeObject;

class NObject extends NField
{
    public function prepare($value)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof TypeObject) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new TypeObject($value);
    }

    public function formatPlain($value, $model = null)
    {
        if (null !== $value) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[] = $k.'='.$v;
            }
            return implode(', ', $result);
        }
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->repository->render('__norm__/nobject/readonly', [
            'value' => $value,
            'self' => $this,
        ]);
    }

    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/nobject/input', [
            'value' => $value,
            'self' => $this,
        ]);
    }
}

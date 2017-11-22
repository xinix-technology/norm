<?php

namespace Norm\Schema;

use Norm\Type\ArrayList;

class NList extends NField
{
    public function prepare($value)
    {
        if (empty($value)) {
            return new ArrayList();
        } elseif ($value instanceof ArrayList) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new ArrayList($value);
    }

    protected function formatPlain($value, $model = null)
    {
        if (null !== $value) {
            return implode(', ', $value->toArray());
        }
    }

    protected function formatInput($value, $model = null)
    {
        return $this->repository->render('__norm__/nlist/input', array(
            'self' => $this,
            'value' => $value,
            'entry' => $model,
        ));
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->repository->render('__norm__/nlist/readonly', array(
            'self' => $this,
            'value' => $value,
            'entry' => $model,
        ));
    }
}

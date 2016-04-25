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
        if (isset($value)) {
            // TODO this checking should available on JsonKit
            $value = substr(phpversion(), 0, 3) > '5.3' ? json_encode($value->toArray(), JSON_PRETTY_PRINT) : json_encode($value->toArray());
        }

        return $value;
    }

    protected function formatInput($value, $model = null)
    {
        return $this->render('__norm__/nlist/input', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->render('__norm__/nlist/readonly', array(
            'value' => $value,
            'entry' => $model,
        ));
    }
}

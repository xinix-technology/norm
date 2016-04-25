<?php

namespace Norm\Schema;

use ArrayAccess;
use Norm\Type\ArrayList;

class NReferenceList extends NReference
{

    public function prepare($value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (is_array($value) || $value instanceof ArrayAccess) {
            $newValue = [];
            foreach ($value as $k => $v) {
                $newValue[] = parent::prepare($v);
            }
            $value = $newValue;
        }

        return new ArrayList($value);
    }

    protected function formatPlain($value, $model = null)
    {
        $result = [];
        foreach ($value as $v) {
            $result[] = parent::formatPlain($v);
        }
        return implode(', ', $result);
    }

    protected function formatReadonly($value, $model = null)
    {
        return $this->render('__norm__/nreferencelist/readonly', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    protected function formatInput($value, $model = null)
    {
        return $this->render('__norm__/nreferencelist/input', array(
            'value' => $value,
            'entry' => $model,
        ));
    }

    protected function formatJson($value, $options = null)
    {
        $result = [];
        foreach ($value as $v) {
            $result[] = (null !== $this['to$key'] && !empty($options['include'])) ? $this->fetch($v) : $v;
        }
        return $result;
    }
}

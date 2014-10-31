<?php

namespace Norm\Schema;

use Bono\App;
use Norm\Norm;
use Norm\Type\NormArray as TypeArray;

class ReferenceArray extends NormArray
{

    public function to($foreign, $foreignKey = null, $foreignLabel = null)
    {
        $argc = func_num_args();
        if ($argc === 1) {
            $this['foreignKey']  = '$id';
            $this['foreignLabel']  = 'name';
        } elseif ($argc === 2) {
            $this['foreignKey'] = '$id';
            $this['foreignLabel'] = $foreignKey;
        } else {
            $this['foreignKey'] = $foreignKey;
            $this['foreignLabel'] = $foreignLabel;
        }

        $this['foreign'] = $foreign;

        return $this;
    }

    public function optionData() {
        if (is_array($this['foreign'])) {
            return $this['foreign'];
        } elseif (is_callable($this['foreign'])) {
            return val($this['foreign']) ?: array();
        }

        if (is_null($this['byCriteria'])) {
            $cursor =  Norm::factory($this['foreign'])->find();
        } else {
            $cursor =  Norm::factory($this['foreign'])->find(val($this['byCriteria']));
        }

        if (isset($this['bySort'])) {
            $cursor->sort($this['bySort']);
        }

        return $cursor;
    }

    public function optionValue($entry)
    {
        return is_scalar($entry) ? $entry : $entry[$this['foreignKey']];
    }

    public function optionLabel($entry)
    {
        if (is_scalar($entry)) {
            $label = $entry;
        } elseif (is_callable($this['foreignLabel'])) {
            $getLabel = $this['foreignLabel'];
            $label = $getLabel($entry);
        } else {
            $label = $entry[$this['foreignLabel']];
        }

        return $label;
    }

    // public function prepare($value)
    // {

    //     if (empty($value)) {
    //         return new TypeArray();
    //     } elseif ($value instanceof TypeArray) {
    //         return $value;
    //     } elseif (is_string($value)) {
    //         $value = json_decode($value, true);
    //     }

    //     return new TypeArray($value);
    // }

    // public function formatPlain($value, $entry = null)
    // {
    //     $value = $this->prepare($value);
    //     if (isset($value)) {
    //         // TODO this checking should available on JsonKit
    //         // if (substr(phpversion(), 0, 3) === '5.3') {
    //         //     $value = json_encode($value->toArray(), JSON_PRETTY_PRINT);
    //         // } else {
    //         $value = json_encode($value->toArray());
    //         // }
    //     }

    //     return $value;
    // }

    // public function formatInput($value, $entry = null)
    // {
    //     return '<textarea name="'.$this['name'].'">'.$this->formatPlain($value, $entry).'</textarea>';
    // }
}

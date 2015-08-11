<?php

namespace Norm\Schema;

use Bono\App;
use Norm\Norm;
use Norm\Type\NormArray as TypeArray;

class ReferenceArray extends NormArray
{

    public function prepare($value)
    {
        if (is_array($value) || $value instanceof TypeArray) {
            $newValue = array();
            foreach ($value as $k => $v) {
                $newValue[] = $this->prepareItem($v);
            }
            $value = $newValue;
        }

        if (empty($value)) {
            return new TypeArray();
        } elseif ($value instanceof TypeArray) {
            return $value;
        } elseif (is_string($value)) {
            $value = json_decode($value, true);
        }

        return new TypeArray($value);
    }

    protected function prepareItem($value)
    {
        if (is_scalar($value) || is_array($this['foreign']) || is_callable($this['foreign'])) {
            return $value;
        }

        if (isset($value['$id'])) {
            // $item =  Norm::factory($this['foreign'])->findOne(array(
            //     $this['foreignKey'] => $value[$this['foreignKey']]
            // ));
            if (isset($value[$this['foreignKey']])) {
                return $value[$this['foreignKey']];
            }
        } else {
            $item = Norm::factory($this['foreign'])->newInstance();
            $item->set($value);
            $item->save();
            return $item[$this['foreignKey']];
        }

        return null;
    }

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

    public function optionData()
    {
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

    public function optionValue($key, $entry)
    {
        if (is_scalar($entry)) {
            return $key;
        } else {
            return $entry[$this['foreignKey']];
        }
    }

    public function optionLabel($key, $entry)
    {
        if (is_scalar($entry)) {
            $label = $entry;
        } elseif ($this['foreignLabel'] instanceof \Closure) {
            $getLabel = $this['foreignLabel'];
            $label = $getLabel($entry);
        } else {
            $label = $entry[$this['foreignLabel']];
        }

        return $label;
    }

    public function formatReadonly($value, $entry = null)
    {
        $html = "<span class=\"field\">\n";
        if (!empty($value)) {
            foreach ($value as $key => $v) {
                $foreignEntry = Norm::factory($this['foreign'])->findOne(array($this['foreignKey'] => $v));
                if (is_string($this['foreignLabel'])) {
                    $label = $foreignEntry[$this['foreignLabel']];
                } elseif (is_callable($this['foreignLabel'])) {
                    $getLabel = $this['foreignLabel'];
                    $label = $getLabel($foreignEntry);
                }
                $html .= '<code>'.$label."</code>\n";
            }
        }
        $html .= "</span>\n";
        return $html;
    }

    public function formatInput($value, $entry = null)
    {
        return $this->render('_schema/reference_array/input', array(
            'value' => $value,
            'entry' => $entry,
        ));
    }

    public function toJSON($value)
    {
        if (!is_string($this['foreign'])) {
            // FIXME should return translated value if non scalar foreign item
            return $value;
        }

        $foreignCollection = Norm::factory($this['foreign']);

        if (Norm::options('include')) {
            $foreignKey = $this['foreignKey'];

            $newValue = array();
            foreach ($value as $k => $v) {
                if (is_null($foreignKey)) {
                    $newValue[] = $foreignCollection->findOne($v);
                } else {
                    $newValue[] = $foreignCollection->findOne(array($this['foreignKey'] => $v));
                }
            }

            $value = $newValue;
        }

        return $value;
    }
}

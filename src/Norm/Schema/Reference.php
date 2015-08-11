<?php

namespace Norm\Schema;

use Norm\Norm;
use Norm\Model;
use Bono\App;

class Reference extends Field
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

    public function by($byCriteria, $bySort = null)
    {
        $this['byCriteria'] = $byCriteria;
        if ($bySort) {
            $this['bySort'] = $bySort;
        }
        return $this;
    }

    /**
     * [findOptions description]
     * @return [type] [description]
     *
     * @deprecated use Reference::optionData() instead.
     *
     */
    public function findOptions()
    {
        trigger_error(__METHOD__.' is deprecated.', E_USER_DEPRECATED);
        return $this->optionData();
    }

    public function optionData()
    {
        if (!is_string($this['foreign'])) {
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

    public function prepare($value)
    {
        if (isset($value['$id']) || $value instanceof \Norm\Model) {
            $value = $value[$this['foreignKey']];
        }

        if (empty($value)) {
            $value = null;
        }

        if (is_string($this['foreign']) && !is_null($this['foreignKey'])) {
            $field = @Norm::factory($this['foreign'])->schema($this['foreignKey']);
            if ($field) {
                $value = $field->prepare($value);
            }
        }

        return $value;
    }

    public function toJSON($value)
    {
        if (!is_string($this['foreign'])) {
            $foreign = val($this['foreign']);
            if (isset($foreign[$value])) {
                if (is_scalar($foreign[$value])) {
                    return $value;
                } else {
                    return $foreign[$value];
                }
            }
            return null;
        }

        $foreignCollection = Norm::factory($this['foreign']);

        if (Norm::options('include')) {
            $foreignKey = $this['foreignKey'];

            if (is_null($foreignKey)) {
                return $foreignCollection->findOne($value);
            } else {
                return $foreignCollection->findOne(array($this['foreignKey'] => $value));
            }
        }

        return $value;
    }

    public function format($name, $valueOrCallable, $entry = null)
    {
        if (is_null($this['foreign'])) {
            throw new \Exception('Reference schema should invoke Reference::to()');
        }

        if (func_num_args() === 3) {
            return parent::format($name, $valueOrCallable, $entry);
        } else {
            return parent::format($name, $valueOrCallable);
        }
    }

    public function formatPlain($value, $entry = null)
    {
        $value = $this->prepare($value);

        $label = '';
        if (is_null($value)) {
            return null;
        } elseif (is_array($this['foreign'])) {
            if (isset($this['foreign'][$value])) {
                $label = $this['foreign'][$value];
            }
        } elseif (is_callable($this['foreign'])) {
            $label = $this['foreign']($value);
        } else {
            $foreignEntry = Norm::factory($this['foreign'])->findOne(array($this['foreignKey'] => $value));

            if (is_string($this['foreignLabel'])) {
                $label = $foreignEntry[$this['foreignLabel']];
            } elseif (is_callable($this['foreignLabel'])) {
                $getLabel = $this['foreignLabel'];
                $label = $getLabel($foreignEntry);
            }
        }
        return $label;
    }

    public function formatInput($value, $entry = null)
    {
        return $this->render('_schema/reference/input', array(
            'self' => $this,
            'value' => $value,
            'entry' => $entry,
        ));
    }
}

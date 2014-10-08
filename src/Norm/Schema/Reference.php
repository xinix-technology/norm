<?php

namespace Norm\Schema;

use Norm\Norm;
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

    public function findOptions()
    {
        if (is_array($this['foreign'])) {
            return $this['foreign'];
        } elseif (is_callable($this['foreign'])) {
            return $this['foreign']();
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

    public function prepare($value)
    {
        if ($value instanceof \Norm\Model) {
            $value = $value->getId();
        }

        if (empty($value)) {
            $value = null;
        }

        if (!is_null($this['foreignKey'])) {
            $field = @Norm::factory($this['foreign'])->schema($this['foreignKey']);
            if ($field) {
                $value = $field->prepare($value);
            }
        }

        return $value;
    }

    public function toJSON($value)
    {
        $foreignCollection = Norm::factory($this['foreign']);

        if (\Norm\Norm::options('include')) {
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
        if (is_array($this['foreign'])) {
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

    public function formatReadonly($value, $entry = null)
    {
        $label = $this->formatPlain($value, $entry) ?: '&nbsp;';
        return '<span class="field">'.$label.'</span>';
    }

    public function formatInput($value, $entry = null)
    {
        $app = App::getInstance();

        $template = $app->theme->resolve('_schema/reference');

        return $app->theme->partial($template, array(
            'self' => $this,
            'value' => $value,
            'entry' => $entry,
            // 'foreignName' => $foreign->name,
            'criteria' => $this['byCriteria'],
        ));

        // if ($template) {
        // } else {
        //     $html = '<select name="'.$this['name'].'"><option value="">---</option>';

        //     foreach($entries as $k => $entry) {
        //         $html .= '<option value="'.$entry[$this['foreignKey']].'" '
        //          .($entry[$this['foreignKey']] == $value ? 'selected' : '').'>'.$entry[$this['foreignLabel']]
        //          .'</option>';
        //     }

        //     $html .= '</select>';

        //     return $html;
        // }

    }

    // public function getRaw($value)
    // {
    //     return $value;
    // }

    // public function cell($value, $entry = null)
    // {
    //     $label = '';

    //     if (empty($value)) {
    //         return '';
    //     }

    //     if (is_array($this['foreign'])) {
    //         return $this['foreign'][$value];
    //     } elseif (is_callable($this['foreign'])) {
    //         return $this['foreign']($value);
    //     } elseif (is_null($this['foreignKey'])) {
    //         $model = Norm::factory($this['foreign'])->findOne($value);
    //     } else {
    //         $criteria = array($this['foreignKey'] => $value);
    //         $model = Norm::factory($this['foreign'])->findOne($criteria);
    //     }

    //     if (is_callable($this['foreignLabel'])) {
    //         $getLabel = $this['foreignLabel'];
    //         $label = $getLabel($model);
    //     } else {
    //         if ($model) {
    //             $label = $model->get($this['foreignLabel']);
    //         }
    //     }

    //     return $label;
    // }
}

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

    public function by($byCriteria)
    {
        $this->set('byCriteria', $byCriteria);
        return $this;
    }

    public function input($value, $entry = null)
    {
        $app = App::getInstance();

        if (is_null($this['foreign'])) {
            throw new \Exception('Reference schema should invoke Reference::to()');
        }

        if ($this['readonly']) {
            if (is_array($this['foreign'])) {
                $label = $this['foreign'][$value];
            } else {
                $entry = Norm::factory($this['foreign'])->findOne(array($this['foreignKey'] => $value));
                if (is_callable($this['foreignLabel'])) {
                    $getLabel = $this['foreignLabel'];
                    $label = $getLabel($entry);
                } else {
                    $label = $entry[$this['foreignLabel']];
                }
            }

            return '<span class="field">'.$label.'</span>';
        }

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

    public function findOptions()
    {
        if (is_array($this['foreign'])) {
            return $this['foreign'];
        }
        // FIXME please fix this to adapt options by $this['byCriteria']
        return Norm::factory($this['foreign'])->find();
    }

    public function getRaw($value)
    {
        return $value;
    }

    public function cell($value, $entry = null)
    {
        $label = '';

        if (empty($value)) {
            return '';
        }

        if (is_null($this['foreignKey'])) {
            $model = Norm::factory($this['foreign'])->findOne($value);
        } else {
            $criteria = array($this['foreignKey'] => $value);
            $model = Norm::factory($this['foreign'])->findOne($criteria);
        }

        if (is_callable($this['foreignLabel'])) {
            $getLabel = $this['foreignLabel'];
            $label = $getLabel($model);
        } else {
            if ($model) {
                $label = $model->get($this['foreignLabel']);
            }
        }

        return $label;
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
            $field = @Norm::factory($this['foreign'])->options['schema'][$this['foreignKey']];
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
            // if ($value == 'sadmin@mail.net') {

            //     var_dump($foreignKey);
            //     exit;
            // }
            if (is_null($foreignKey)) {
                return $foreignCollection->findOne($value);
            } else {
                return $foreignCollection->findOne(array($this['foreignKey'] => $value));
            }
        }

        return $value;
    }
}

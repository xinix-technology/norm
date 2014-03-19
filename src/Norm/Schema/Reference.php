<?php

namespace Norm\Schema;

use Norm\Norm;
use Bono\App;

// TODO recheck this implementation later
// FIXME remove all properties and add attributes by set()
class Reference extends Field {

    // protected $foreign;
    // protected $foreignLabel;
    // protected $foreignKey;
    // protected $byCriteria;

    public function to($foreign, $foreignKey, $foreignLabel = null) {
        $this->set('foreign', $foreign);
        if (is_null($foreignLabel)) {
            $this->set('foreignLabel', $foreignKey);
            $this->set('foreignKey', null);
        } else {
            $this->set('foreignLabel', $foreignLabel);
            $this->set('foreignKey', $foreignKey);
        }

        if (!$this['foreignKey']) {
            $this->set('foreignKey', '$id');
        }
        return $this;
    }

    public function by($byCriteria) {
        $this->set('byCriteria', $byCriteria);
        return $this;
    }

    public function input($value, $entry = NULL) {
        $app = App::getInstance();

        $foreign = Norm::factory($this['foreign']);

        if ($this['readonly']) {
            if (is_null($this['foreignKey'])) {
                $entry = Norm::factory($this['foreign'])->findOne($value);
            } else {
                $criteria = array($this['foreignKey'] => $value);
                $entry = Norm::factory($this['foreign'])->findOne($criteria);
            }

            if (is_callable($this['foreignLabel'])) {
                $getLabel = $this['foreignLabel'];
                $label = $getLabel($entry);
            } else {
                $label = $entry[$this['foreignLabel']];
            }
            return '<span class="field">'.$label.'</span>';
        }

        $template = '_schema/reference';
        $template = $app->theme->resolve($template);

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
        //         $html .= '<option value="'.$entry[$this['foreignKey']].'" '.($entry[$this['foreignKey']] == $value ? 'selected' : '').'>'.$entry[$this['foreignLabel']].'</option>';
        //     }

        //     $html .= '</select>';

        //     return $html;
        // }

    }

    public function getRaw($value) {
        return $value;
    }

    public function cell($value, $entry = NULL) {
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

    public function prepare($value) {
        if ($value instanceof \Norm\Model) {
            $value = $value->getId();
        }

        if (empty($value)) {
            $value = NULL;
        }

        if(! is_null($this['foreignKey'])) {
            $field = @Norm::factory($this['foreign'])->options['schema'][$this['foreignKey']];
            if ($field) {
                $value = $field->prepare($value);
            }
        }

        return $value;
    }

    public function toJSON($value) {
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

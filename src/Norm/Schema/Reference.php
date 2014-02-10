<?php

namespace Norm\Schema;

use Norm\Norm;

class Reference extends Field {

    protected $foreign;
    protected $foreignLabel;
    protected $foreignKey;

    public function to($foreign, $foreignKey = null, $foreignLabel) {
        $this->foreign = $foreign;
        $this->foreignLabel = $foreignLabel;
        $this->foreignKey = $foreignKey;
        return $this;
    }

    public function input($value, $entry = NULL) {
        $foreign = Norm::factory($this->foreign);

        if ($this['readonly']) {
            if (is_null($this->foreignKey)) {
                $entry = Norm::factory($this->foreign)->findOne($this->foreignKey);
            } else {
                $criteria = array($this->foreignKey => $value);
                $entry = Norm::factory($this->foreign)->findOne($criteria);
            }

            if (is_callable($this->foreignLabel)) {
                $getLabel = $this->foreignLabel;
                $label = $getLabel($entry);
            } else {
                $label = $entry->get($this->foreignLabel);
            }
            return '<span class="field">'.$label.'</span>';
        }

        $options = array();
        $entries = $foreign->find();
        foreach ($entries as $entry) {
            if (is_callable($this->foreignLabel)) {
                $getLabel = $this->foreignLabel;
                $label = $getLabel($entry);
            } else {
                $label = $entry->get($this->foreignLabel);
            }

            if (is_null($this->foreignKey)) {
                $options[] = '<option value="'.$entry->getId().'" '.($entry->getId() === $value ? 'selected' : '').'>'.$label.'</option>';
            } else {
                $options[] = '<option value="'.$entry->get($this->foreignKey).'" '.($entry->get($this->foreignKey) === $value ? 'selected' : '').'>'.$label.'</option>';
            }

        }
        return '
            <select name="'.$this['name'].'"><option value="">---</option>'.implode('', $options).'</select>
        ';
    }

    public function cell($value, $entry = NULL) {
        if (empty($value)) {
            return '';
        }

        if (is_null($this->foreignKey)) {
            $model = Norm::factory($this->foreign)->findOne($this->foreignKey);
        } else {
            $criteria = array($this->foreignKey => $value);
            $model = Norm::factory($this->foreign)->findOne($criteria);
        }

        if (is_callable($this->foreignLabel)) {
            $getLabel = $this->foreignLabel;
            $label = $getLabel($model);
        } else {
            $label = $model->get($this->foreignLabel);
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

        if(! is_null($this->foreignKey)) {
            $value = Norm::factory($this->foreign)->options['schema'][$this->foreignKey]->prepare($value);
        }

        return $value;
    }
}

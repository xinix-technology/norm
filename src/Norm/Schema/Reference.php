<?php

namespace Norm\Schema;

use Norm\Norm;

class Reference extends Field {

    protected $foreign;
    protected $foreignLabel;

    public function to($foreign, $foreignLabel) {
        $this->foreign = $foreign;
        $this->foreignLabel = $foreignLabel;
        return $this;
    }

    public function input($value, $entry = NULL) {
        $foreign = Norm::factory($this->foreign);

        if ($this['readonly']) {
            $entry = $foreign->findOne($value);
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
            $options[] = '<option value="'.$entry->getId().'" '.($entry->getId() === $value ? 'selected' : '').'>'.$label.'</option>';
        }
        return '
            <select name="'.$this['name'].'"><option value="">---</option>'.implode('', $options).'</select>
        ';
    }

    public function cell($value, $entry = NULL) {
        if (empty($value)) {
            return '';
        }
        $model = Norm::factory($this->foreign)->findOne($value);
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

        return $value;
    }
}
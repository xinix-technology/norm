<?php

namespace Norm\Schema;

use Norm\Norm;
use Bono\App;

// TODO recheck this implementation later
class Reference extends Field {

    protected $foreign;
    protected $foreignLabel;
    protected $foreignKey;
    protected $byCriteria;

    public function to($foreign, $foreignKey, $foreignLabel = null) {
        $this->foreign = $foreign;
        if (is_null($foreignLabel)) {
            $this->foreignLabel = $foreignKey;
            $this->foreignKey = null;
        } else {
            $this->foreignLabel = $foreignLabel;
            $this->foreignKey = $foreignKey;
        }
        return $this;
    }

    public function by($byCriteria) {
        $this->byCriteria = $byCriteria;
        return $this;
    }

    public function input($value, $entry = NULL) {
        $app = App::getInstance();

        $foreign = Norm::factory($this->foreign);

        if ($this['readonly']) {
            if (is_null($this->foreignKey)) {

                $entry = Norm::factory($this->foreign)->findOne($value);
            } else {
                $criteria = array($this->foreignKey => $value);
                $entry = Norm::factory($this->foreign)->findOne($criteria);
            }

            if (is_callable($this->foreignLabel)) {
                $getLabel = $this->foreignLabel;
                $label = $getLabel($entry);
            } else {
                $label = $entry[$this->foreignLabel];
            }
            return '<span class="field">'.$label.'</span>';
        }

        $criteria = array();
        if ($this->byCriteria) {
            if ($entry) {
                foreach ($this->byCriteria as $key => $v) {
                    $criteria[$key] = @$entry[$v];
                }
            }
        }
        $entries = $foreign->find($criteria);

        return $app->theme->partial('_schema/reference', array(
            'entries' => $entries,
            'self' => $this,
            'value' => $value,
            'entry' => $entry,
            'foreignName' => $foreign->name,
            'criteria' => $this->byCriteria,
        ));
    }

    public function getRaw($value) {
        return $value;
    }

    public function cell($value, $entry = NULL) {
        $label = '';

        if (empty($value)) {
            return '';
        }

        if (is_null($this->foreignKey)) {
            $model = Norm::factory($this->foreign)->findOne($value);
        } else {
            $criteria = array($this->foreignKey => $value);
            $model = Norm::factory($this->foreign)->findOne($criteria);
        }

        if (is_callable($this->foreignLabel)) {
            $getLabel = $this->foreignLabel;
            $label = $getLabel($model);
        } else {
            if ($model) {
                $label = $model->get($this->foreignLabel);
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

        if(! is_null($this->foreignKey)) {
            $value = Norm::factory($this->foreign)->options['schema'][$this->foreignKey]->prepare($value);
        }

        return $value;
    }

    public function getForeignLabel() {
        return $this->foreignLabel;
    }

    public function getForeignKey() {
        return $this->foreignKey;
    }

    public function getForeign() {
        return $this->foreign;
    }
}

<?php

namespace Norm\Schema;

class NormDate extends Field
{


    public function prepare($value)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof \Norm\Type\NDate) {
            return $value;
        } elseif ($value instanceof \DateTime) {
            $t = $value->format('c');
        } elseif (is_string($value)) {
            $t = date('c', strtotime($value));
        } else {
            $t = date('c', (int) $value);
        }
        return new \Norm\Type\NDate($t);
    }

    public function formatInput($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<input type="date" class="'.$this->inputClass().'" '.$this->inputAttributes().' name="'.$this['name'].'" value="'.($value ? $value->format('Y-m-d') : '').
            '" placeholder="'.$this['label'].
            '" autocomplete="off" />';
    }

    public function formatPlain($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return ($value ? $value->format('Y-m-d') : null);
    }

    public function formatReadonly($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<span class="field '.$this->inputClass().'">'.($value ? $value->format('Y-m-d') : '&nbsp;').'</span>';
    }
   
}

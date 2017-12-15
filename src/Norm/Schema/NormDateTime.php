<?php

namespace Norm\Schema;

class NormDateTime extends Field
{
    public function prepare($value)
    {
        if (empty($value)) {
            return null;
        } elseif ($value instanceof \Norm\Type\NDateTime) {
            return $value;
        } elseif ($value instanceof \DateTime) {
            $t = $value->format('c');
        } elseif (is_string($value)) {
            $t = date('c', strtotime($value));
        } else {
            $t = date('c', (int) $value);
        }
        return new \Norm\Type\NDateTime($t);
    }

    public function formatInput($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<input type="datetime-local" class="'.$this->inputClass().'" '. $this->inputAttributes().' name="'.$this['name'].'" value="'.
            ($value ? $value->format("Y-m-d\TH:i") : '').'" placeholder="'.
            $this['label'].'" autocomplete="off" />';
    }

    

    public function formatReadonly($value, $entry = null)
    {
        $value = $this->prepare($value);
        if ($value) {
            $value->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }

        return '<span class="field">'.($value ? $value->format('c') : '&nbsp;').'</span>';
    }

    
}

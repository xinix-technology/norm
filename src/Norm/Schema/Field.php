<?php

namespace Norm\Schema;

abstract class Field implements \ArrayAccess {
    protected $multi = false;

    protected $attributes = array();

    public function __construct($name, $label = '') {
        $this->set('name', $name);
        $this->set('label', $label);
    }

    public function has($k) {
        return array_key_exists($k, $this->attributes);
    }

    public function set($k, $v) {
        $this->attributes[$k] = $v;
        return $this;
    }

    public function get($k, $default = NULL) {
        if (!$this->has($k)) {
            return $default;
        }
        return $this->attributes[$k];
    }

    public function offsetExists ($offset) {
        return $this->has($offset);
    }

    public function offsetGet ($offset) {
        return $this->get($offset);
    }

    public function offsetSet ($offset , $value) {
        $this->set($offset, $value);
    }

    public function offsetUnset ($offset) {
        unset($this->attributes[$offset]);
    }

    public function input($value, $entry = NULL) {
        if ($this['readOnly']) {
            return '<span class="field">'.$value.'</span>';
        }
        if ($format = $this['inputFormat']) {
            return $format($value, $entry);
        }
        return '<input type="text" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry);
        }
        return $value;
    }
}
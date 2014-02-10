<?php

namespace Norm\Schema;

use \ROH\Util\Inflector;
use Norm\Filter\Filter;

abstract class Field implements \ArrayAccess {

    static protected $instances = array();

    protected $multi = false;

    protected $attributes = array();

    protected $filter = array();

    public static function getInstance($name = '', $label = NULL) {
        $Field = get_called_class();

        if (empty($name)) {
            if (!isset(static::$instances[$Field])) {
                static::$instances[$Field] = new $Field($name, $label);
            }

            return static::$instances[$Field];
        }

        return new $Field($name, $label);
    }

    public function __construct($name, $label = NULL) {
        if (is_null($label)) {
            $label = Inflector::humanize($name);
        }
        $this->set('name', $name);
        $this->set('label', $label);
    }

    public function prepare($value) {
        return $value;
    }

    public function filter() {
        if (func_num_args() == 0) {
            return $this->filter;
        }

        $filters = func_get_args();
        foreach ($filters as $filter) {
            if (is_string($filter)) {
                $filter = explode('|', $filter);
                foreach ($filter as $f) {

                    $baseF = explode(':', trim($f));
                    $baseF = $baseF[0];
                    $this['filter-'.$baseF] = true;

                    $this->filter[] = $f;
                }
            } else {
                $this->filter[] = $filter;
            }
        }

        return $this;

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

    public function label() {
        return '<label>'.$this['label'].($this['filter-required'] ? '*' : '').'</label>';
    }

    public function input($value, $entry = NULL) {
        if ($this['readonly']) {
            if ($format = $this['inputFormat']) {
                return $format($value, $entry, $this);
            } else {
                return '<span class="field">'.$value.'</span>';
            }
        }
        if ($format = $this['inputFormat']) {
            return $format($value, $entry, $this);
        }
        return '<input type="text" name="'.$this['name'].'" value="'.(@$value).'" placeholder="'.$this['label'].'" autocomplete="off" />';
    }

    public function cell($value, $entry = NULL) {
        if ($this->has('cellFormat') && $format = $this['cellFormat']) {
            return $format($value, $entry, $this);
        }
        return $value;
    }

    public function cellRaw($value) {
        return $value;
    }

    public function getInputInRaw($value) {
        return '<span class="field">'.$value.'</span>';
    }
}

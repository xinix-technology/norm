<?php

namespace Norm\Schema;

use \ROH\Util\Inflector;
use Norm\Filter\Filter;

abstract class Field implements \ArrayAccess
{

    static protected $instances = array();

    protected $attributes = array();

    protected $filter = array();

    protected $presets = array();

    /**
     * Get new instance of field schema
     *
     * DEPRECATED Method deprecated and will be replaced by Field::create on 0.2.0
     * @param  string $name  [description]
     * @param  [type] $label [description]
     * @return [type]        [description]
     */
    public static function getInstance($name = '', $label = null)
    {
        return static::create($name, $label);
    }

    public static function create($name = '', $label = null)
    {
        $Field = get_called_class();

        if (empty($name)) {
            if (!isset(static::$instances[$Field])) {
                static::$instances[$Field] = new $Field($name, $label);
            }

            return static::$instances[$Field];
        }

        return new $Field($name, $label);
    }

    public function __construct($name, $label = null)
    {
        if (is_null($label)) {
            $label = Inflector::humanize($name);
        }
        $this->set('name', $name);
        $this->set('label', $label);
    }

    public function prepare($value)
    {
        return $value;
    }

    public function preset($name, $callable = null)
    {
        if (is_null($callable)) {
            $name = $name ?: 'plain';

            if (isset($this->presets[$name])) {
                return $this->presets[$name];
            }

            $method = 'preset'.strtoupper($name[0]).substr($name, 1);

            if (method_exists($this, $method)) {
                return array($this, $method);
            } else {
                return array($this, 'plain');
            }
        }
        $this->presets[$name] = $callable;
        return $this;
    }

    public function filter()
    {
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

    public function has($k)
    {
        return array_key_exists($k, $this->attributes);
    }

    public function set($k, $v)
    {
        $this->attributes[$k] = $v;
        return $this;
    }

    public function get($k, $default = null)
    {
        if (!$this->has($k)) {
            return $default;
        }
        return $this->attributes[$k];
    }

    public function offsetExists ($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet ($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet ($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset ($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function label($plain = false)
    {

        $label = l($this['label']);
        if ($plain) {
            return $plain;
        }
        return '<label>'.$label.($this['filter-required'] ? '*' : '').'</label>';
    }


    public function toJSON($value)
    {
        return $value;
    }

    public function render($preset, $value, $entry = null)
    {
        // force to render readonly preset if you want to render readonly input
        if ($preset === 'input' && $this['readonly']) {
            $preset = 'readonly';
        }
        $fn = $this->preset($preset);

        if (is_callable($fn)) {
            return call_user_func($fn, $value, $entry);
        } else {
            throw new \Exception('Preset is not a callable');
        }
    }

    public function presetPlain($value, $entry = null)
    {
        return $value;
    }

    public function presetReadonly($value, $entry = null)
    {
        return "<span class=\"field\">".($value ?: '&nbsp;')."</span>";
    }

    public function presetInput($value, $entry = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }
        return '<input type="text" name="'.$this['name'].'" value="'.$value.'" placeholder="'.l($this['label']).
            '" autocomplete="off" />';
    }

    // DEPRECATED method: input, cell, cellRaw, getInputInRaw replaced with render with preset

    // public function cell($value, $entry = null)
    // {
    //     if ($this->has('cellFormat') && $format = $this['cellFormat']) {
    //         return $format($value, $entry, $this);
    //     }
    //     return $value;
    // }
    //
    // public function cellRaw($value)
    // {
    //     return $value;
    // }

    // public function getInputInRaw($value)
    // {
    //     return '<span class="field">'.$value.'</span>';
    // }
}

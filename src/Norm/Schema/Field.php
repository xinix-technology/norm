<?php

namespace Norm\Schema;

use ROH\Util\Inflector;
use Norm\Filter\Filter;

abstract class Field implements \ArrayAccess, \Iterator, \JsonKit\JsonSerializer
{

    static protected $instances = array();

    protected $attributes = array();

    protected $filter = array();

    protected $formats = array();

    protected $reader;

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

    public function __construct($name = null, $label = null)
    {
        if (is_null($label)) {
            $label = Inflector::humanize($name);
        }

        $this->set('name', $name);
        $this->set('label', $label);

        if (!empty($name) && $name[0] === '$') {
            $this->set('hidden', true);
        }
    }

    public function prepare($value)
    {
        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    public function read($valueOrCallable)
    {
        if (is_callable($valueOrCallable)) {
            $this->reader = $valueOrCallable;
            return $this;
        } elseif (is_callable($this->reader)) {
            return call_user_func($this->reader, $valueOrCallable);
        }
    }

    public function hasReader()
    {
        return $this->reader ? true : false;
    }

    public function format($name, $valueOrCallable, $entry = null)
    {
        if ($name === 'input' && $this['readonly']) {
            $name = 'readonly';
        }

        // set new format
        if (func_num_args() === 2 && $valueOrCallable instanceof \Closure) {
            $this->formats[$name] = $valueOrCallable;
            return $this;
        }

        // extract formatter function
        if (isset($this->formats[$name])) {
            $fn = $this->formats[$name];
        } else {
            $method = 'format'.strtoupper($name[0]).substr($name, 1);
            $fn = array($this, $method);
        }

        // get formatted value
        if (is_callable($fn)) {
            return call_user_func($fn, $valueOrCallable, $entry);
        } else {
            throw new \Exception("[Norm/Field] Formatter not found. [$method]");
        }
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
                    $this['filter'.strtoupper($baseF[0]).substr($baseF, 1)] = true;

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

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function label($plain = false)
    {

        $label = l($this['label']);
        if ($plain) {
            return $label;
        }
        return '<label>'.$label.($this['filterRequired'] ? '*' : '').'</label>';
    }

    public function toJSON($value)
    {
        return $value;
    }

    public function formatPlain($value, $entry = null)
    {
        return $value;
    }

    public function formatReadonly($value, $entry = null)
    {
        return "<span class=\"field\">".($this->formatPlain($value, $entry) ?: '&nbsp;')."</span>";
    }

    public function formatInput($value, $entry = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }

        return $this->render('_schema/field/input', array(
            'self' => $this,
            'value' => $value,
            'entry' => $entry
        ));
        
    }

    public function inputAttributes(){
        $attributes = array();

        if($this['input_attributes']){
            $attributes = $this['input_attributes'];
        }

        return implode(" ",$attributes);


    }

    public function inputClass(){
        $class = array();

        $app = \Bono\App::getInstance();

        if(!empty($app->config('bono.theme')['htmlclass'])){
            $class = $app->config('bono.theme')['htmlclass'];
        }

        
        
        if(!empty($this['class'])){
            $class = array_merge($class,$this['class']);
        }

        

        return implode(" ",$class);
    }

    public function render($template, array $context = array())
    {
        $app = \Bono\App::getInstance();

        $context['self'] = $this;

        $template = $app->theme->resolve($template);

        return $app->theme->partial($template, $context);
    }

    public function current()
    {
        return current($this->attributes);
    }

    public function next()
    {
        return next($this->attributes);
    }

    public function key()
    {
        return key($this->attributes);
    }

    public function valid()
    {
        return $this->current();
    }

    public function rewind()
    {
        return reset($this->attributes);
    }

    public function jsonSerialize()
    {
        return $this->attributes;
    }
}

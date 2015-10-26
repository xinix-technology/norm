<?php

namespace Norm\Schema;

use Closure;
use Exception;
use InvalidArgumentException;
use ROH\Util\Inflector;
use ROH\Util\Collection as UtilCollection;
use Norm\Norm;
use Norm\Filter;

abstract class Field extends UtilCollection
{
    protected $schema;

    protected $filter = [];

    protected $formatters;

    protected $reader;

    public static function create($label = null)
    {
        $Field = get_called_class();

        return new $Field($label);
    }

    public function __construct($label = null)
    {
        $this['name'] = '';
        $this['label'] = $label;

        $this->formatters = [
            'readonly' => [$this, 'formatReadonly'],
            'input' => [$this, 'formatInput'],
            'plain' => [$this, 'formatPlain'],
        ];

        parent::__construct();
    }

    public function forSchema($schema, $name)
    {
        $this->schema = $schema;

        $this['name'] = $name;

        if ($this['name'][0] === '$') {
            $this->hidden();
        }

        if (is_null($this['label'])) {
            $this['label'] = Inflector::humanize($this['name']);
        }


        return $this;
    }

    public function factory($collectionId = null, $connectionId = null)
    {
        if (is_null($this->schema)) {
            throw new InvalidArgumentException('Schema is undefined');
        }
        return $this->schema->factory($collectionId, $connectionId);
    }

    public function translate($message)
    {
        if (is_null($this->schema)) {
            throw new InvalidArgumentException('Schema is undefined');
        }
        return $this->schema->translate($message);
    }

    public function prepare($value)
    {
        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    public function read($model)
    {
        $reader = $this->reader;
        return $reader($model);
    }

    public function withReader($reader)
    {
        $this->reader = $reader;
        return $this;
    }

    public function hasReader()
    {
        return isset($this->reader);
    }

    public function getFormatter($format)
    {
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    public function format($format, $value, $model = null)
    {
        if ($format === 'input' && $this['readonly']) {
            $format = 'readonly';
        }

        $formatter = $this->getFormatter($format);
        if (isset($formatter)) {
            return $formatter($this->prepare($value), $model);
        }
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function withFilter()
    {
        $filters = func_get_args();
        foreach ($filters as $filter) {
            if (is_string($filter)) {
                $filter = explode('|', $filter);
                foreach ($filter as $f) {
                    $farr = explode(':', $f);
                    $this['filter.' . $farr[0]] = array_slice($farr, 1);
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
        $label = $this->translate($this['label']);
        if ($plain) {
            return $label;
        }
        return '<label>'.$label.(isset($this['filter.required']) ? '*' : '').'</label>';
    }

    public function toJSON($value)
    {
        return $value;
    }

    public function formatPlain($value, $model = null)
    {
        return $value;
    }

    public function formatReadonly($value, $model = null)
    {
        return "<span class=\"field\">".($this->formatPlain($value, $model) ?: '&nbsp;')."</span>";
    }

    public function formatInput($value, $model = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }
        return '<input type="text" name="'.$this['name'].'" value="'.$value.'" placeholder="' .
            $this->translate($this['label']). '" autocomplete="off" />';
    }

    public function render($template, array $context = array())
    {
        if (is_null($this->schema)) {
            throw new InvalidArgumentException('Schema is undefined');
        }

        $context['self'] = $this;

        return $this->schema->render($template, $context);
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

    public function transient($transient = true)
    {
        $this['transient'] = $transient;
        return $this;
    }

    public function hidden($hidden = true)
    {
        $this['hidden'] = $hidden;
        return $this;
    }
}

<?php

namespace Norm\Schema;

use Norm\Exception\NormException;
use ROH\Util\Inflector;
use Norm\Normable;
use Norm\Repository;
use Norm\Schema;

abstract class NField extends Normable
{
    protected $schema;

    protected $filter = [];

    protected $formatters;

    protected $reader;

    public function __construct(Repository $repository, Schema $schema, array $options = [])
    {
        if (null === $schema) {
            throw new NormException('Schema is mandatory!');
        }

        if (!isset($options['name'])) {
            throw new NormException('Option name is mandatory!');
        }

        $this->schema = $schema;

        $this->formatters = [
            'readonly' => [$this, 'formatReadonly'],
            'input' => [$this, 'formatInput'],
            'plain' => [$this, 'formatPlain'],
        ];

        if (isset($options['filter'])) {
            $this->addFilter($options['filter']);
        }

        parent::__construct($repository, $options);

        if ($this['name'][0] === '$') {
            $this->hidden();
        }
        if (is_null($this['label'])) {
            $this['label'] = Inflector::humanize($this['name']);
        }

    }

    // public function forSchema($schema, $name)
    // {
    //     $this->schema = $schema;
    //     $this['name'] = $name ?: '';
    //     if ($this['name'][0] === '$') {
    //         $this->hidden();
    //     }
    //     if (is_null($this['label'])) {
    //         $this['label'] = Inflector::humanize($this['name']);
    //     }
    //     return $this;
    // }

    public function factory($collectionId = '', $connectionId = '')
    {
        return $this->schema->factory($collectionId, $connectionId);
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

    public function setReader($reader)
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

    public function format($format, $value)
    {
        if ($format === 'input' && $this['readonly']) {
            $format = 'readonly';
        }

        $formatter = $this->getFormatter($format);
        if (isset($formatter)) {
            return $formatter($this->prepare($value));
        }
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function addFilter()
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
            } elseif (is_array($filter)) {
                foreach ($filter as $f) {
                    $this->addFilter($f);
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

    public function label()
    {
        return '<label>'. $this->translate($this['label']).(isset($this['filter.required']) ? '*' : '').'</label>';
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

        return $this->render('__norm__/nfield/input', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
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

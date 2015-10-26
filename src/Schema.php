<?php
namespace Norm;

use Exception;
use Norm\Model;
use Norm\Collection;
use ROH\Util\Collection as UtilCollection;
use ROH\Util\StringFormatter;
use Norm\Schema\Unknown;
use Norm\Schema\Field;
use InvalidArgumentException;

class Schema extends UtilCollection
{
    protected $collection;

    protected $formatters;

    protected $firstKey;

    public function __construct(Collection $collection, $fields = array())
    {
        $this->collection = $collection;

        parent::__construct();

        foreach ($fields as $key => $field) {
            $this->withField($key, $field);
        }

        $this->formatters = [
            '' => [$this, 'defaultFormatter'],
        ];
    }

    public function withField($key, $field)
    {
        if (!($field instanceof Field)) {
            throw new InvalidArgumentException('Field for schema must be instance of Field');
        }

        if (is_null($this->firstKey)) {
            $this->firstKey = $key;
        }

        $field->forSchema($this, $key);

        $this[$key] = $field;

        return $this;
    }

    public function getFilterRules()
    {
        $rules = [];
        foreach ($this as $k => $field) {
            if (is_null($field)) {
                continue;
            }

            $rules[$k] = array(
                'label' => $field['label'],
                'filters' => $field->getFilter(),
            );
        }
        return $rules;
    }

    public function defaultFormatter($model)
    {
        return $model[$this->firstKey];
    }

    public function getFormatter($format)
    {
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    public function withFormatter($format, $formatter)
    {
        if (is_string($formatter)) {
            $fmt = function ($model) use ($formatter) {
                if (empty($formatter)) {
                    return $model->format();
                } else {
                    $sf = new StringFormatter($formatter);
                    if ($sf->isStatic()) {
                        return $model[$formatter];
                    } else {
                        return $sf->format($model);
                    }
                }
            };
        } elseif (is_callable($formatter)) {
            $fmt = $formatter;
        } else {
            throw new InvalidArgumentException('Formatter should be callable or string format');
        }
        return $fmt;
    }

    public function format(Model $model, $format = null)
    {
        $formatter = $this->getFormatter($format ?: '');

        if (is_null($formatter)) {
            throw new Exception('Formatter '.$format.' not found');
        }

        return $formatter($model);
    }

    public function factory($collectionId = null, $connectionId = null)
    {
        return $this->collection->factory($collectionId, $connectionId);
    }

    public function translate($message)
    {
        return $this->collection->translate($message);
    }

    public function render($template, array $context = array())
    {
        return $this->collection->render($template, $context);
    }

    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            $this->attributes[$key] = (new Unknown())->forSchema($this, $key);
        }
        return $this->attributes[$key];
    }
}

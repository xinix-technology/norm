<?php
namespace Norm;

use Norm\Exception\NormException;
use Norm\Model;
use Norm\Collection;
use ROH\Util\StringFormatter;
use Norm\Schema\NUnknown;
use Norm\Schema\NField;
use InvalidArgumentException;

class Schema extends Normable
{
    protected $collection;

    protected $formatters;

    protected $firstKey;

    public function __construct(Repository $repository, Collection $collection, array $fields = [])
    {
        $this->collection = $collection;

        parent::__construct($repository);

        foreach ($fields as $field) {
            $this->addField($field);
        }

        $this->formatters = [
            'plain' => [$this, 'formatPlain'],
        ];
    }

    public function addField($meta)
    {
        if ($meta instanceof NField) {
            $field = $meta;
        } else {
            $field = $this->resolve($meta, [
                'schema' => $this,
            ]);
        }

        $this[$field['name']] = $field;

        if (is_null($this->firstKey)) {
            $this->firstKey = $field['name'];
        }

        return $this;
    }

    public function getFilterRules()
    {
        $rules = [];
        foreach ($this as $k => $field) {
            if (is_null($field)) {
                continue;
            }

            $rules[$k] = [
                'label' => $field['label'],
                'filters' => $field->getFilter(),
            ];
        }
        return $rules;
    }

    public function formatPlain($model)
    {
        return $model[$this->firstKey];
    }

    public function getFormatter($format)
    {
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    public function addFormatter($format, $formatter)
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

    public function format($format, Model $model)
    {
        $formatter = $this->getFormatter($format ?: '');

        if (is_null($formatter)) {
            throw new NormException('Formatter ' . $format . ' not found');
        }

        return $formatter($model);
    }

    public function offsetGet($key)
    {
        if (!$this->offsetExists($key)) {
            // $this->attributes[$key] =
            return new NUnknown($this->repository, $this, [
                'name' => $key
            ]);
        }
        return $this->attributes[$key];
    }

    public function factory($collectionId = '', $connectionId = '')
    {
        if ('' === $collectionId) {
            return $this->collection;
        }
        return $this->repository->factory($collectionId, $connectionId);
    }
}

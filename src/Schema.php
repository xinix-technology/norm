<?php
namespace Norm;

use Norm\Exception\NormException;
use Norm\Model;
use Norm\Collection;
use ROH\Util\StringFormatter;
use Norm\Schema\NUnknown;
use Norm\Schema\NField;

class Schema extends Normable
{
    /**
     * [$collection description]
     * @var Collection
     */
    protected $collection;

    /**
     * [$formatters description]
     * @var array
     */
    protected $formatters;

    /**
     * [$firstKey description]
     * @var string
     */
    protected $firstKey;

    /**
     * [__construct description]
     * @param Repository $repository [description]
     * @param Collection $collection [description]
     * @param array      $fields     [description]
     */
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

    /**
     * [addField description]
     * @param array|NField $metaOrField [description]
     */
    public function addField($metaOrField)
    {
        if ($metaOrField instanceof NField) {
            $field = $metaOrField;
        } else {
            $field = $this->resolve($metaOrField, [
                'schema' => $this,
            ]);
        }

        $this[$field['name']] = $field;

        if (is_null($this->firstKey)) {
            $this->firstKey = $field['name'];
        }

        return $field;
    }

    /**
     * [getFilterRules description]
     * @return array [description]
     */
    public function getFilterRules()
    {
        $rules = [];
        foreach ($this as $k => $field) {
            // there will be no null field
            // if (is_null($field)) {
            //     continue;
            // }

            $rules[$k] = [
                'label' => $field['label'],
                'filters' => $field->getFilter(),
            ];

        }
        return $rules;
    }

    /**
     * [formatPlain description]
     * @param  Model  $model [description]
     * @return string        [description]
     */
    protected function formatPlain(Model $model)
    {
        return $model[$this->firstKey];
    }

    /**
     * [addFormatter description]
     * @param string          $format    [description]
     * @param string|callable $formatter [description]
     */
    public function addFormatter($format, $formatter)
    {
        if (is_string($formatter)) {
            $sf = new StringFormatter($formatter);
            $fmt = function ($model) use ($formatter, $sf) {
                // if ('' === $formatter) {
                //     return $model->format();
                // } else {
                if ($sf->isStatic()) {
                    return isset($model[$formatter]) ? $model[$formatter] : '';
                } else {
                    return $sf->format($model);
                }
                // }
            };
        } elseif (is_callable($formatter)) {
            $fmt = $formatter;
        } else {
            throw new NormException('Formatter should be callable or string format');
        }
        $this->formatters[$format] = $fmt;
        return $fmt;
    }

    /**
     * [getFormatter description]
     * @param  string   $format [description]
     * @return callable         [description]
     */
    public function getFormatter($format = 'plain')
    {
        if (!is_string($format)) {
            throw new NormException('Format key must be string');
        }
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    /**
     * [format description]
     * @param  string $format [description]
     * @param  Model  $model  [description]
     * @return string         [description]
     */
    public function format($format, Model $model)
    {
        $formatter = $this->getFormatter($format);

        if (is_null($formatter)) {
            throw new NormException('Formatter ' . $format . ' not found');
        }

        return $formatter($model);
    }

    /**
     * [offsetGet description]
     * @param  string $key [description]
     * @return NField      [description]
     */
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

    /**
     * [factory description]
     * @param  string     $collectionId [description]
     * @param  string     $connectionId [description]
     * @return Collection               [description]
     */
    public function factory($collectionId = '', $connectionId = '')
    {
        if ('' === $collectionId) {
            return $this->collection;
        }
        return $this->collection->factory($collectionId, $connectionId);
    }
}

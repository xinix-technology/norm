<?php
namespace Norm;

use Norm\Exception\NormException;
use Norm\Model;
use Norm\Collection;
use ROH\Util\StringFormatter;
use Norm\Schema\NUnknown;
use Norm\Schema\NField;
use Norm\Normable;

class Schema extends Normable
{
    /**
     * [$formatters description]
     * @var array
     */
    protected $formatters;

    /**
     * [$fields description]
     * @var array
     */
    protected $fields = [];

    /**
     * [$firstField description]
     * @var string
     */
    protected $firstField;

    /**
     * [__construct description]
     * @param Collection $collection [description]
     * @param array      $fields     [description]
     */
    public function __construct(Collection $collection = null)
    {
        parent::__construct($collection);

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

        $this->fields[$field['name']] = $field;

        if (is_null($this->firstField)) {
            $this->firstField = $field['name'];
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
        foreach ($this->fields as $k => $field) {
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
        if (null === $this->firstField) {
            throw new NormException('Cannot format explicit schema fields');
        }
        return $model[$this->firstField];
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
    public function getField($key)
    {
        if (!isset($this->fields[$key])) {
            return new NUnknown($this, $key);
        }
        return $this->fields[$key];
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
            return $this->parent;
        }
        return $this->parent->factory($collectionId, $connectionId);
    }

    public function __debugInfo()
    {
        $result = [];
        foreach ($this->fields as $key => $value) {
            $result[$key] = get_class($value);
        }
        return $result;
    }
}

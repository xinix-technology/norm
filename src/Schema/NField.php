<?php
namespace Norm\Schema;

use Norm\Exception\NormException;
use ROH\Util\Inflector;
use Norm\Normable;
use Norm\Repository;
use Norm\Model;
use Norm\Schema;
use ArrayAccess;

abstract class NField extends Normable implements ArrayAccess
{
    /**
     * [$attributes description]
     * @var array
     */
    protected $attributes;

    /**
     * [$filter description]
     * @var array
     */
    protected $filter = [];

    /**
     * [$formatters description]
     * @var array
     */
    protected $formatters;

    /**
     * [$reader description]
     * @var callable
     */
    protected $reader;

    /**
     * [__construct description]
     * @param string           $name       [description]
     * @param string|array     $filter     [description]
     * @param array            $attributes [description]
     */
    public function __construct(Schema $schema = null, $name = '', $filter = null, array $attributes = [])
    {
        if ((!is_string($name) && !is_array($name)) || empty($name)) {
            throw new NormException('Name (2nd argument) must be string or array, and must not empty');
        }

        parent::__construct($schema);

        $this->attributes = $attributes;

        $this->formatters = [
            'readonly' => [$this, 'formatReadonly'],
            'input' => [$this, 'formatInput'],
            'plain' => [$this, 'formatPlain'],
            'json' => [$this, 'formatJson'],
            'label' => [$this, 'formatLabel'],
        ];

        if (!empty($filter)) {
            $this->addFilter($filter);
        }

        if (is_array($name)) {
            if (count($name) !== 2) {
                throw new NormException('Name (2nd argument) must be array consists of name and label');
            }
            $this['name'] = $name[0];
            $this['label'] = $name[1];
        } else {
            $this['name'] = $name;
            $this['label'] = Inflector::humanize($name);
        }

        if ('$' === $this['name'][0]) {
            $this['hidden'] = true;
        }
    }

    /**
     * [factory description]
     * @param  string $collectionId [description]
     * @param  string $connectionId [description]
     * @return Collection           [description]
     */
    public function factory($collectionId = '', $connectionId = '')
    {
        if (null === $this->parent) {
            throw new NormException('Field does not have schema yet!');
        }
        return $this->parent->factory($collectionId, $connectionId);
    }

    /**
     * [prepare description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public function prepare($value)
    {
        return filter_var($value, FILTER_SANITIZE_STRING);
    }

    /**
     * [read description]
     * @param  Model  $model [description]
     * @return [type]        [description]
     */
    public function read(Model $model)
    {
        $reader = $this->reader;
        return $reader($model);
    }

    /**
     * [setReader description]
     * @param callable $reader [description]
     */
    public function setReader(callable $reader)
    {
        $this->reader = $reader;
        return $this;
    }

    /**
     * [hasReader description]
     * @return boolean [description]
     */
    public function hasReader()
    {
        return isset($this->reader);
    }

    /**
     * [getFormatter description]
     * @param  string $format [description]
     * @return callable         [description]
     */
    public function getFormatter($format)
    {
        return isset($this->formatters[$format]) ? $this->formatters[$format] : null;
    }

    /**
     * [format description]
     * @param  string $format [description]
     * @param  mixed  $value  [description]
     * @return string         [description]
     */
    public function format($format, $value = null, $arg1 = null)
    {
        if ($format === 'input' && $this['readonly']) {
            $format = 'readonly';
        }

        $formatter = $this->getFormatter($format);
        if (null === $formatter) {
            throw new NormException('Formatter not found, ' . $format);
        }
        return $formatter($this->prepare($value), $arg1);
    }

    /**
     * [getFilter description]
     * @return [type] [description]
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * [addFilter description]
     */
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

    /**
     * [has description]
     * @param  string  $k [description]
     * @return boolean    [description]
     */
    public function has($k)
    {
        return array_key_exists($k, $this->attributes);
    }

    /**
     * [set description]
     * @param string $k [description]
     * @param mixed  $v [description]
     */
    public function set($k, $v)
    {
        $this->attributes[$k] = $v;
        return $this;
    }

    /**
     * [get description]
     * @param  string $k       [description]
     * @param  mixed  $default [description]
     * @return mixed           [description]
     */
    public function get($k, $default = null)
    {
        if (!$this->has($k)) {
            return $default;
        }
        return $this->attributes[$k];
    }

    /**
     * [offsetExists description]
     * @param  string   $offset [description]
     * @return boolean          [description]
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * [offsetGet description]
     * @param  string $offset [description]
     * @return mixed          [description]
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * [offsetSet description]
     * @param  string $offset [description]
     * @param  mixed  $value  [description]
     * @return mixed          [description]
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * [offsetUnset description]
     * @param  string $offset [description]
     * @return mixed          [description]
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    protected function formatLabel($value, $model = null)
    {
        return $this->render('__norm__/nfield/label', [
            'self' => $this,
        ]);
    }

    protected function formatJson($value, $model = null)
    {
        return $value;
    }

    protected function formatPlain($value, $model = null)
    {
        return $value;
    }

    protected function formatReadonly($value, $model = null)
    {
        if (!empty($value)) {
            $value = htmlentities($value);
        }

        return $this->render('__norm__/nfield/readonly', [
            'self' => $this,
            'value' => $value,
            'model' => $model,
        ]);
    }

    protected function formatInput($value, $model = null)
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

    // public function current()
    // {
    //     return current($this->attributes);
    // }

    // public function next()
    // {
    //     return next($this->attributes);
    // }

    // public function key()
    // {
    //     return key($this->attributes);
    // }

    // public function valid()
    // {
    //     return $this->current();
    // }

    // public function rewind()
    // {
    //     return reset($this->attributes);
    // }

    // public function jsonSerialize()
    // {
    //     return $this->attributes;
    // }

    /**
     * [transient description]
     * @param  boolean $transient [description]
     * @return [type]             [description]
     */
    // public function transient($transient = true)
    // {
    //     $this['transient'] = $transient;
    //     return $this;
    // }

    /**
     * [hidden description]
     * @param  boolean $hidden [description]
     * @return [type]          [description]
     */
    // public function hidden($hidden = true)
    // {
    //     $this['hidden'] = $hidden;
    //     return $this;
    // }

    // public function end()
    // {
    //     return $this->parent;
    // }

    /**
     * [__debugInfo description]
     * @return [type] [description]
     */
    public function __debugInfo()
    {
        return $this->attributes;
    }
}

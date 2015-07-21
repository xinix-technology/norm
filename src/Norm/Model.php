<?php

namespace Norm;

use Closure;
use Norm\Norm;
use Exception;
use JsonKit\JsonKit;

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Model implements \JsonKit\JsonSerializer, \ArrayAccess
{

    /**
     * Constants for fetching toArray method.
     * FETCH_ALL       will fetch all attributes of model
     * FETCH_PUBLISHED will fetch published attributes of model
     * FETCH_HIDDEN    will fetch hidden attributes of model
     */
    const FETCH_ALL       = 'FETCH_ALL';
    const FETCH_RAW       = 'FETCH_RAW';
    const FETCH_PUBLISHED = 'FETCH_PUBLISHED';
    const FETCH_HIDDEN    = 'FETCH_HIDDEN';

    /**
     * State of document
     *
     * STATE_DETACHED
     * STATE_ATTACHED
     * STATE_REMOVED
     */
    const STATE_DETACHED = 'STATE_DETACHED';
    const STATE_ATTACHED = 'STATE_ATTACHED';
    const STATE_REMOVED  = 'STATE_REMOVED';

    /**
     * Collection object of model.
     *
     * @var \Bono\Collection
     */
    protected $collection;

    /**
     * Model attributes. Mostly only published attributes that stored here.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Model old attributes before setting new attributes
     *
     * @var array
     */
    protected $oldAttributes;

    /**
     * Model id.
     *
     * @var int|string
     */
    protected $id = null;

    /**
     * State of current document.
     *
     * @var string
     */
    protected $state = '';

    /**
     * Constructor.
     *
     * @param array  $attributes Attributes of model.
     * @param array  $options    Options to construct the model.
     */
    public function __construct(array $attributes = array(), $options = array())
    {
        if (isset($options['collection'])) {
            $this->collection = $options['collection'];
        }

        $this->reset();
        $this->sync($attributes);
    }

    /**
     * Getter for collection
     *
     * @return \Norm\Collection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * Reset model to deleted state
     *
     * @return void
     */
    public function reset()
    {
        $this->id = null;
        $this->attributes = array();
    }

    /**
     * Get id of model.
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id of model.
     *
     * @return int|string
     */
    public function setId($givenId)
    {
        if (!isset($this->id)) {
            $this->id = $givenId;
        }
        return $this->id;
    }

    /**
     * Determine if offset is exist in attributes.
     *
     * @method has
     *
     * @param string $offset
     *
     * @return boolean
     */
    public function has($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    /**
     * Get the attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if ($key === '$id') {
            return $this->getId();
        }

        $schema = $this->schema($key);
        if (isset($schema) and $schema->hasReader()) {
            return $schema->read($this);
        }
        return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
    }

    /**
     * Dump attributes raw data.
     *
     * @method dump
     *
     * @return array
     */
    public function dump()
    {
        $attributes = array();

        if ($this->id) {
            $attributes['$id'] = $this->id;
        }

        foreach ($this->attributes as $key => $value) {
            $schema = $this->schema($key);

            if (! empty($schema['transient'])) {
                continue;
            }

            $attributes[$key] = $value;
        }

        return $attributes;
    }

    /**
     * Add an attributes data.
     *
     * @method add
     *
     * @param string $key
     * @param mixed  $value
     */
    public function add($key, $value)
    {
        if (! isset($this->attributes[$key])) {
            $this->attributes[$key] = array();
        }

        $this->attributes[$key][] = $value;

        return $this;
    }

    /**
     * Set attribute(s).
     *
     * @param string|array $key
     * @param string       $value Optional.
     */
    public function set($key, $value = '')
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                if ($k !== '$id') {
                    $this->set($k, $v);
                }
            }
        } elseif ($key === '$id') {
            throw new Exception('[Norm/Model] Restricting set for $id.');
        } else {
            $this->attributes[$key] = $this->prepare($key, $value);
        }

        return $this;
    }

    /**
     * Clear attributes value.
     *
     * @method clear
     *
     * @param string $key
     *
     * @return \Norm\Model
     */
    public function clear($key = null)
    {
        if (func_num_args() === 0) {
            $this->attributes = array();
        } elseif ($key === '$id') {
            throw new Exception('[Norm/Model] Restricting clear for $id.');
        } else {
            unset($this->attributes[$key]);
        }

        return $this;
    }

    /**
     * Sync the existing attributes with new values. After update or insert, this method used to modify the existing attributes.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function sync($attributes)
    {
        if (isset($attributes['$id'])) {
            $this->state = static::STATE_ATTACHED;
            $this->id = $attributes['$id'];
        } else {
            foreach ($this->schema() as $key => $field) {
                if ($field->has('default')) {
                    $attributes[$key] = $field['default'];
                }
            }

            $this->state = static::STATE_DETACHED;
        }

        $this->set($attributes);
        $this->populateOld();
    }

    /**
     * Prepare model to be sync'd.
     *
     * @method prepare
     *
     * @param string $key
     * @param string $value
     * @param mixed  $schema
     *
     * @return [type]
     */
    public function prepare($key, $value, $schema = null)
    {
        if ($this->collection) {
            return $this->collection->prepare($key, $value, $schema);
        } else {
            return $value;
        }
    }

    /**
     * Save the model.
     *
     * @return void
     */
    public function save($options = array())
    {
        $this->collection->save($this, $options);
    }

    /**
     * Run filter hook.
     *
     * @method filter
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function filter($fieldName = null)
    {
        return $this->collection->filter($this, $fieldName);
    }

    /**
     * Remove the model.
     *
     * @return int Status of removal.
     */
    public function remove()
    {
        return $this->collection->remove($this);
    }

    /**
     * Get array structure of model
     *
     * @param mixed $fetchType
     *
     * @return array
     */
    public function toArray($fetchType = Model::FETCH_ALL)
    {
        if ($fetchType === Model::FETCH_RAW) {
            return $this->attributes;
        }

        $attributes = array();

        if (empty($this->attributes)) {
            $this->attributes = array();
        }

        if ($fetchType === Model::FETCH_ALL or $fetchType === Model::FETCH_HIDDEN) {
            $attributes['$type'] = $this->getClass();
            $attributes['$id'] = $this->getId();

            foreach ($this->attributes as $key => $value) {
                if ($key[0] === '$') {
                    $attributes[$key] = $value;
                }
            }
        }

        if ($fetchType === Model::FETCH_ALL or $fetchType === Model::FETCH_PUBLISHED) {
            foreach ($this->attributes as $key => $value) {
                if ($key[0] !== '$') {
                    $attributes[$key] = $value;
                }
            }
        }

        return $attributes;
    }

    /**
     * Determine if offset exists in attributes.
     *
     * @method offsetExists
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get value from attributes.
     *
     * @method offsetExists
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Set value in attributes.
     *
     * @method offsetExists
     *
     * @param string $offset
     * @param mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        return $this->set($offset, $value);
    }

    /**
     * Remove an attributes value.
     *
     * @method offsetExists
     *
     * @param string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        return $this->clear($offset);
    }

    /**
     * Implement the json serializer normalizing the data structures.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        if (! Norm::options('include')) {
            return $this->toArray();
        }

        $destination = array();
        $source =  $this->toArray();

        $schema = $this->collection->schema();

        foreach ($source as $key => $value) {
            if (isset($schema[$key]) and isset($value)) {
                $destination[$key] = $schema[$key]->toJSON($value);
            } else {
                $destination[$key] = $value;
            }

            $destination[$key] = JsonKit::replaceObject($destination[$key]);
        }

        return $destination;
    }

    /**
     * Set original attributes.
     *
     * @method populateOld
     *
     * @return void
     */
    protected function populateOld()
    {
        $this->oldAttributes = $this->attributes ?: array();
    }

    /**
     * Get original attributes
     *
     * @method previous
     *
     * @param string $key
     *
     * @return mixed
     */
    public function previous($key = null)
    {
        if (is_null($key)) {
            return $this->oldAttributes;
        }

        return $this->oldAttributes[$key];
    }

    /**
     * Determine if model is a new document.
     *
     * @method isNew
     *
     * @return boolean
     */
    public function isNew()
    {
        return ($this->state === static::STATE_DETACHED);
    }

    /**
     * Determine if document has been removed.
     *
     * @method isRemoved
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return ($this->state === static::STATE_REMOVED);
    }

    /**
     * Get schema configuration.
     *
     * @method schema
     *
     * @param string $key
     *
     * @return mixed
     */
    public function schema($key = null)
    {
        if (func_num_args() === 0) {
            return $this->collection->schema();
        } else {
            return $this->collection->schema($key);
        }
    }

    /**
     * Get schema configuration by offset name.
     *
     * @method schemaByIndex
     *
     * @param string $index
     *
     * @return mixed
     */
    public function schemaByIndex($index)
    {
        $schema = array();

        foreach ($this->collection->schema() as $value) {
            $schema[] = $value;
        }

        return (empty($schema[$index])) ? null : $schema[$index];
    }

    /**
     * Format the model to HTML file. Bind it's attributes to view.
     *
     * @method format
     *
     * @param string $field
     * @param string $format
     *
     * @return mixed
     */
    public function format($field = null, $format = null)
    {
        $numArgs = func_num_args();

        if ($numArgs === 0) {
            $formatter = $this->collection->option('format');

            if (is_null($formatter)) {
                $schema = $this->schemaByIndex(0);
                if (!is_null($schema)) {
                    return (isset($this[$schema['name']])) ? val($this[$schema['name']]) : null;
                } else {
                    return '-- no formatter and schema --';
                }
            } else {
                if ($formatter instanceof Closure) {
                    return $formatter($this);
                } elseif (is_string($formatter)) {

                    $result = preg_replace_callback('/{(\w+)}/', function($matches) {
                        return $this->format($matches[1]);
                    }, $formatter);

                    return $result;
                } else {
                    throw new Exception('Unknown format for Model formatter.');
                }
            }
        } else {
            $format = $format ?: 'plain';

            $schema = $this->schema($field);

            // TODO return value if no formatter or just throw exception?
            if (is_null($schema)) {
                throw new Exception("[Norm/Model] No formatter [$format] for field [$field].");
            } else {
                $value = isset($this[$field]) ? val($this[$field]) : null;
                return $schema->format($format, $value, $this);
            }
        }
    }

    /**
     * Get implementation name.
     *
     * @method getClass
     *
     * @return string
     */
    public function getClass()
    {
        return $this->collection->getClass();
    }
}

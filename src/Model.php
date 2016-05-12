<?php

namespace Norm;

use ArrayAccess;
use Norm\Exception\NormException;
use JsonKit\JsonKit;
use JsonKit\JsonSerializer;
use Norm\Normable;

/**
 * Base class for hookable implementation
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Model extends Normable implements JsonSerializer, ArrayAccess
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
     * [$collection description]
     * @var Norm\Collection
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
    protected $id;

    /**
     * State of current document.
     *
     * @var string
     */
    protected $state;

    /**
     * Constructor.
     *
     * @param array  $attributes Attributes of model.
     * @param array  $options    Options to construct the model.
     */
    public function __construct(Collection $collection, array $attributes = [], array $options = [])
    {
        $this->collection = $collection;
        $this->repository = $collection->getRepository();

        $this->reset();
        $this->sync($attributes);
    }

    /**
     * Getter for collection
     *
     * @return Norm\Collection
     */
    // public function collection
    // {
    //     return $this->parent;
    // }

    /**
     * Reset model to deleted state
     *
     * @return void
     */
    public function reset($removed = false)
    {
        if ($removed) {
            $this->state = static::STATE_REMOVED;
        } else {
            $this->state = static::STATE_DETACHED;
            $this->id = null;
            $this->attributes = [];
        }
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
        return isset($this->attributes[$offset]);
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

        $field = $this->collection->getField($key);
        if ($field->hasReader()) {
            return $field->read($this);
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
        $attributes = [];

        if ($this->id) {
            $attributes['$id'] = $this->id;
        }

        foreach ($this->attributes as $key => $value) {
            if (null === $this->collection->getField($key)->get('transient')) {
                $attributes[$key] = $value;
            }
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
    // public function add($key, $value)
    // {
    //     if (! isset($this->attributes[$key])) {
    //         $this->attributes[$key] = [];
    //     }

    //     $this->attributes[$key][] = $value;

    //     return $this;
    // }

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
            throw new NormException('Restricting model to set for $id.');
        } else {
            $this->attributes[$key] = $this->collection->getField($key)->prepare($value);
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
     * @return Norm\Model
     */
    public function clear($key = null)
    {
        if (func_num_args() === 0) {
            $this->attributes = [];
        } elseif ($key === '$id') {
            throw new NormException('Restricting model to clear for $id.');
        } else {
            unset($this->attributes[$key]);
        }

        return $this;
    }

    /**
     * Save the model.
     *
     * @return void
     */
    public function save(array $options = [])
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
    public function remove(array $options = [])
    {
        return $this->collection->remove($this, $options);
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

        $attributes = [];

        // if (!is_array($this->attributes)) {
        //     $this->attributes = [];
        // }

        if ($fetchType === Model::FETCH_ALL or $fetchType === Model::FETCH_HIDDEN) {
            $attributes['$type'] = $this->collection->getName();
            $attributes['$id'] = $this->getId();

            foreach ($this->attributes as $key => $value) {
                if ($this->collection->getField($key)->get('hidden')) {
                    $attributes[$key] = $value;
                }
            }
        }

        if ($fetchType === Model::FETCH_ALL or $fetchType === Model::FETCH_PUBLISHED) {
            foreach ($this->attributes as $key => $value) {
                if (!$this->collection->getField($key)->get('hidden')) {
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
    public function jsonSerialize($options = [])
    {
        // FIXME revisit this later
        // if (! Norm::options('include')) {
        //     return $this->toArray();
        // }

        $destination = [];
        $source =  $this->toArray();

        foreach ($source as $key => $value) {
            $destination[$key] = JsonKit::replaceObject(
                $this->collection->getField($key)->format('json', $value, $options)
            );
        }

        return $destination;
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
        if (null === $key) {
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
     * Format the model to HTML string. Bind it's attributes to view.
     *
     * @method format
     *
     * @param string $format
     * @param string $field
     *
     * @return mixed
     */
    public function format($format = 'plain', $field = null)
    {
        switch (func_num_args()) {
            case 0:
            case 1:
                return $this->collection->format($format, $this);
            default:
                return $this->collection->getField($field)->format($format, $this[$field]);
        }
    }

    /**
     * Get implementation name.
     *
     * @method getCollectionId
     *
     * @return string
     */
    // public function getCollectionId()
    // {
    //     return $this->parent->getId();
    // }

    // public function getCollectionName()
    // {
    //     return $this->parent->getName();
    // }

    /**
     * Sync the existing attributes with new values. After update or insert,
     * this method used to modify the existing attributes.
     *
     * @param array $attributes
     *
     * @return void
     */
    public function sync(array $attributes)
    {
        if (isset($attributes['$id'])) {
            $this->state = static::STATE_ATTACHED;
            $this->id = $attributes['$id'];
        }

        $this->set($attributes);
        $this->populateOld();
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
        $this->oldAttributes = $this->attributes ?: [];
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }
}

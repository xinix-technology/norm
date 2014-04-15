<?php

namespace Norm;

/**
 * Norm\Model
 *
 * Default model implementation.
 */

class Model implements \JsonKit\JsonSerializer, \ArrayAccess
{

    /**
     * Constants for fetching toArray method.
     * FETCH_ALL       will fetch all attributes of model
     * FETCH_PUBLISHED will fetch published attributes of model
     * FETCH_HIDDEN    will fetch hidden attributes of model
     */
    const FETCH_ALL         = 'FETCH_ALL';
    const FETCH_RAW         = 'FETCH_RAW';
    const FETCH_PUBLISHED   = 'FETCH_PUBLISHED';
    const FETCH_HIDDEN      = 'FETCH_HIDDEN';

    /**
     * Collection object of model.
     *
     * @var Bono\Collection
     */
    public $collection;

    /**
     * Connection to whom this model belongs to.
     *
     * @var Bono\Connection
     */
    public $connection;

    /**
     * Model name.
     *
     * @var string
     */
    public $name;

    /**
     * Model class name.
     *
     * @var string
     */
    public $clazz;

    /**
     * Model attributes. Mostly only published attributes that stored here.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Model old attributes before setting new attributes
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
     * Constructor.
     *
     * @param array  $attributes Attributes of model.
     * @param array  $options    Options to construct the model.
     */
    public function __construct(array $attributes = array(), $options = array())
    {
        $this->collection = $options['collection'];

        $this->connection = $this->collection->connection;
        $this->name = $this->collection->name;
        $this->clazz = $this->collection->clazz;

        if (isset($attributes['$id'])) {
            $this->id = $attributes['$id'];
            // FIXME reekoheek $attributes['$id'] should be removed. is it ok?
            unset($attributes['$id']);
        }

        $this->set($attributes);

        // populate oldAttributes
        $this->populateOld();


    }

    /**
     * Reset model to deleted state
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
            $this->set('$id', $givenId);
        }
        return $this->id;
    }

    public function has($offset)
    {
        return array_key_exists($offset, $this->attributes);
    }

    /**
     * Get the attribute.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key)
    {
        $getter = 'get_'.$key;
        if (method_exists($this, $getter)) {
            return $this->$getter($key);
        } elseif (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function dump()
    {
        return $this->attributes;
    }

    public function add($key, $value)
    {
        if (!isset($this->attributes[$key])) {
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
                $this->set($k, $v);
            }
        } else {
            $value = $this->prepare($key, $value);

            $setter = 'set_'.$key;
            if (method_exists($this, $setter)) {
                $this->$setter($key, $value);
            } else {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function rmset($key)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->unset($k, $v);
            }
        } else {
            unset($this->attributes[$key]);
        }
        return $this;
    }

    /**
     * Sync the existing attributes with new values. After update or insert,
     * this method used to modify the existing attributes.
     *
     * @param  [type] $attributes [description]
     * @return [type]             [description]
     */
    public function sync($attributes)
    {
        foreach ($attributes as $key => $attribute) {
            if ($key[0] !== '$') {
                $this->attributes[$key] = $attribute;
            }
        }

        if (isset($attributes['$id'])) {
            $this->attributes['$id'] = $attributes['$id'];
            $this->id = $attributes['$id'];
        }

        $this->populateOld();

    }

    public function prepare($key, $value, $schema = null)
    {
        return $this->collection->prepare($key, $value, $schema);
    }

    /**
     * Save the model.
     *
     * @return int Status of saving.
     */
    public function save($options = array())
    {
        return $this->collection->save($this, $options);
    }

    /**
     * Remove the model.
     * @return int Status of removal.
     */
    public function remove()
    {
        return $this->collection->remove($this);
    }

    /**
     * Get array structure of model
     * @param  mixed  $fetchType
     * @return array
     */
    public function toArray($fetchType = Model::FETCH_ALL)
    {
        if ($fetchType === Model::FETCH_RAW) {
            return $this->attributes;
        }

        $attributes = array();

        if ($fetchType === Model::FETCH_ALL || $fetchType === Model::FETCH_HIDDEN) {
            $attributes['$type'] = $this->clazz;
            $attributes['$id'] = $this->getId();

            foreach ($this->attributes as $key => $value) {
                if ($key[0] === '$') {
                    $attributes[$key] = $value;
                }
            }
        }

        if ($fetchType === Model::FETCH_ALL || $fetchType === Model::FETCH_PUBLISHED) {
            foreach ($this->attributes as $key => $value) {
                if ($key[0] !== '$') {
                    $attributes[$key] = $value;
                }

            }
        }

        return $attributes;
    }

    public function offsetExists ($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet ($offset)
    {
        if ($offset === '$id') {
            return $this->getId();
        }
        return $this->get($offset);
    }

    public function offsetSet ($offset, $value)
    {
        return $this->set($offset, $value);
    }

    public function offsetUnset ($offset)
    {
        return $this->rmset($offset);
    }

    /**
     * Implement the json serializer normalizing the data structures.
     */
    public function jsonSerialize()
    {
        if (!\Norm\Norm::options('include')) {
            return $this->toArray();
        }

        $destination = array();
        $source =  $this->toArray();

        $schema = $this->collection->schema();

        foreach ($source as $key => $value) {
            if (isset($schema[$key]) && !is_null($value)) {
                $destination[$key] = $schema[$key]->toJSON($value);
            } else {
                $destination[$key] = $value;
            }
            $destination[$key] = \JsonKit\JsonKit::replaceObject($destination[$key]);
        }
        return $destination;
    }

    protected function populateOld()
    {
        $this->oldAttributes = array();
        if (is_array($this->attributes)) {
            foreach ($this->attributes as $k => $v) {
                $this->oldAttributes[$k] = $v;
            }
        }
    }

    public function previous($key)
    {
        return $this->oldAttributes[$key];
    }
}

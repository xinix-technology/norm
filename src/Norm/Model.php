<?php

namespace Norm;

/**
 * Norm\Model
 *
 * Default model implementation.
 */

class Model implements \JsonKit\JsonSerializer {

    /**
     * Constants for fetching toArray method.
     * FETCH_ALL       will fetch all attributes of model
     * FETCH_PUBLISHED will fetch published attributes of model
     * FETCH_HIDDEN    will fetch hidden attributes of model
     */
    const FETCH_ALL         = 'FETCH_ALL';
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
     * Model id.
     *
     * @var int|string
     */
    protected $id = NULL;

    /**
     * Constructor.
     *
     * @param array  $attributes Attributes of model.
     * @param array  $options    Options to construct the model.
     */
    public function __construct(array $attributes = array(), $options = array()) {
        $this->collection = $options['collection'];

        $this->connection = $this->collection->connection;
        $this->name = $this->collection->name;
        $this->clazz = $this->collection->clazz;

        if (isset($attributes['$id'])) {
            $this->id = $attributes['$id'];
        }

        $this->attributes = $attributes;
    }

    /**
     * Get id of model.
     *
     * @return int|string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id of model.
     *
     * @return int|string
     */
    public function setId($givenId) {
        if (!isset($this->id)) {
            $this->id = $givenId;
        }
    }

    /**
     * Get the attribute.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key) {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function dump() {
        return $this->attributes;
    }

    /**
     * Set attribute(s).
     *
     * @param string|array $key
     * @param string       $value Optional.
     */
    public function set($key, $value = '') {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            if (is_null($value)) {
                unset($this->attributes[$key]);
            } else {
                $this->attributes[$key] = $value;
            }
        }
    }

    /**
     * Sync the existing attributes with new values. After update or insert,
     * this method used to modify the existing attributes.
     *
     * @param  [type] $attributes [description]
     * @return [type]             [description]
     */
    public function sync($attributes) {
        foreach ($attributes as $key => $attribute) {
            if ($key[0] !== '$') {
                $this->attributes[$key] = $attribute;
            }
        }

        if (isset($attributes['$id'])) {
            $this->attributes['$id'] = $attributes['$id'];
            $this->id = $attributes['$id'];
        }

    }

    /**
     * Save the model.
     *
     * @return int Status of saving.
     */
    public function save() {
        return $this->collection->save($this);
    }

    /**
     * Remove the model.
     * @return int Status of removal.
     */
    public function remove() {
        return $this->collection->remove($this);
    }

    /**
     * Get array structure of model
     * @param  mixed  $fetchType
     * @return array
     */
    public function toArray($fetchType = Model::FETCH_ALL) {
        $arrObj = new \ArrayObject($this->attributes);

        $attributes = $arrObj->getArrayCopy();
        if ($fetchType == Model::FETCH_ALL) {
            $attributes = array(
                '$type' => $this->clazz,
                ) + $attributes;
        } elseif ($fetchType == Model::FETCH_HIDDEN) {
            $newattributes = array(
                '$type' => $this->clazz,
            );
            if (isset($attributes['$id'])) {
                $newattributes['$id'] = $attributes['$id'];
            }
            $attributes = $newattributes;
        } elseif ($fetchType == Model::FETCH_PUBLISHED) {
            unset($attributes['$id']);
        }
        return $attributes;
    }

    /**
     * Implement the json serializer normalizing the data structures.
     */
    public function jsonSerialize() {
        return $this->toArray();
    }

}

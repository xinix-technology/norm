<?php

namespace Norm;

class Model implements \JsonSerializable {

    public $collection;

    public $connection;

    public $name;

    public $clazz;

    protected $attributes;

    protected $id = NULL;

    public function __construct(array $attributes = array(), $options) {
        $this->collection = $options['collection'];

        $this->connection = $this->collection->connection;
        $this->name = $this->collection->name;
        $this->clazz = $this->collection->clazz;

        $this->attributes = $attributes;
    }

    public function get($key) {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function set($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function save() {
        $this->collection->save($this);
    }

    public function remove() {
        $this->collection->remove($this);
    }

    public function toArray() {
        $attributes = (new \ArrayObject($this->attributes))->getArrayCopy();
        $attributes = array(
            '_type' => $this->clazz,
            '_id' => (string) $attributes['_id'],
            ) + $attributes;
        return $attributes;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}
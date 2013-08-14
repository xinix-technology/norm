<?php

namespace Norm;

class Model implements \JsonSerializable {

    const FETCH_ALL = 'FETCH_ALL';
    const FETCH_PUBLISHED = 'FETCH_PUBLISHED';
    const FETCH_HIDDEN = 'FETCH_HIDDEN';

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

        if (isset($attributes['$id'])) {
            $this->id = $attributes['$id'];
        }

        $this->attributes = $attributes;
    }

    public function getId() {
        return $this->id;
    }

    public function get($key) {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }
    }

    public function set($key, $value = '') {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }
        } else {
            $this->attributes[$key] = $value;
        }
    }

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

    public function save() {
        $result = $this->collection->save($this);
        return $result;
    }

    public function remove() {
        return $this->collection->remove($this);
    }

    public function toArray($fetchType = Model::FETCH_ALL) {
        $attributes = (new \ArrayObject($this->attributes))->getArrayCopy();
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

    public function jsonSerialize() {
        return $this->toArray();
    }
}
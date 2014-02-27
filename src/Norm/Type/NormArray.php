<?php

namespace Norm\Type;

class NormArray implements \JsonKit\JsonSerializer, \ArrayAccess {
    protected $attributes = array();

    public function __construct($attributes) {
        if ($attributes instanceof NormArray) {
            $attributes = $attributes->toArray();
        }
        $this->attributes = $attributes;
    }

    public function jsonSerialize() {
        throw new \Exception('Unimplemented yet!');
        return 'not serialized!';
    }

    public function offsetGet($key) {
        return $this->attributes[$key];
    }

    public function offsetSet($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function offsetExists($key) {
        return isset($this->attributes[$key]);
    }

    public function offsetUnset($key) {
        unset($this->attributes[$key]);
    }

    public function has($o) {
        return in_array($o, $this->attributes);
    }

    public function toArray() {
        return $this->attributes;
    }

}
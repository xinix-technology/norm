<?php

namespace Norm;

class Schema {
    const TYPE_STRING   = 'string';
    const TYPE_INT      = 'int';
    const TYPE_DOUBLE   = 'double';

    protected $schemes;

    public function __construct($schemes = array()) {
        $this->schemes = $schemes;
    }

    public function get($name) {
        return isset($this->schemes[$name]) ? $this->schemes[$name] : NULL;
    }

    public function toArray() {
        return $this->schemes;
    }
}
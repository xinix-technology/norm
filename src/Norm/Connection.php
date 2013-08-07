<?php

namespace Norm;

abstract class Connection {
    protected $options;

    public function __construct($options) {
        $this->options = $options;

        $this->initialize($options);
    }

    public function getOptions() {
        return $this->options;
    }

    abstract public function initialize($options);
    abstract public function listCollections();
    abstract public function getCollection($name);
    abstract public function factory($collectionName);
}
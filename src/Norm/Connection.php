<?php

namespace Norm;

abstract class Connection extends Hookable {
    protected $options;

    protected $raw;

    protected $collections = array();

    public function __construct($options) {
        $this->options = $options;

        $this->initialize($options);
    }

    public function getOptions() {
        return $this->options;
    }

    public function getName() {
        return $this->options['name'];
    }

    public function getRaw() {
        return $this->raw;
    }

    public function factory($collectionName) {
        if (!isset($this->collections[$collectionName])) {
            $collection = Norm::createCollection(array(
                'name' => $collectionName,
                'connection' => $this,
            ));

            $this->applyHook('norm.after.factory', $collection);

            $this->collections[$collectionName] = $collection;
        }

        return $this->collections[$collectionName];
    }

    public function hasCollection($name) {
        $collections = $this->listCollections();
        foreach ($collections as $key => $collection) {
            if ($collection === $name) {
                return true;
            }
        }
        return false;
    }

    abstract public function initialize($options);
    abstract public function migrate(Collection $collection);
    abstract public function listCollections();
    abstract public function prepare($object);
    abstract public function query(Collection $collection);
    abstract public function save(Collection $collection, Model $model);
    abstract public function remove(Collection $collection, $model);
}

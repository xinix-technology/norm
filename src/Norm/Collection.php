<?php

namespace Norm;

use Doctrine\Common\Inflector\Inflector;
use Norm\Model;

class Collection {
    public $clazz;
    public $name;
    public $connection;

    public $filter;

    protected $schema;

    public function __construct(array $options = array()) {
        $this->clazz = Inflector::classify($options['name']);
        $this->name = Inflector::tableize($this->clazz);
        $this->connection = $options['connection'];
    }

    public function schema($schema = NULL) {
        if (is_null($schema)) {
            return $this->schema;
        } else {
            $this->schema = $schema;
        }
    }

    public function hydrate($cursor) {
        $results = array();

        foreach ($cursor as $doc) {
            $doc = $this->connection->prepare($doc);

            $results[] = new Model($doc, array(
                'collection' => $this,
            ));
        }
        return $results;
    }

    public function filter(array $filter = null) {
        if (isset($filter)) {
            if (!isset($this->filter)) {
                $this->filter = array();
            }

            $this->filter = $this->filter + $filter;
        }
    }

    public function find(array $filter = null) {
        $this->filter($filter);

        return $this->hydrate($this->connection->query($this));
    }

    public function findOne(array $filter = null) {
        $this->filter($filter);

        $cursor = $this->connection->query($this);

        if ($cursor->hasNext()) {
            $o = $cursor->getNext();
            $hydrate = $this->hydrate(array($o));
            return $hydrate[0];
        }
    }

    public function newInstance($cloned = array()) {
        if ($cloned instanceof Model) {
            $cloned = $cloned->toArray(Model::FETCH_PUBLISHED);
        }
        return new Model($cloned, array('collection' => $this));
    }

    public function save(Model $model) {
        return $this->connection->save($this, $model);
    }

    public function remove($model) {
        return $this->connection->remove($this, $model);
    }

    public function jsonSerialize() {
        return $this->clazz;
    }

}
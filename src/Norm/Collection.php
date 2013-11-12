<?php

namespace Norm;

use Reekoheek\Util\Inflector;
use Norm\Model;

class Collection implements \JsonKit\JsonSerializer {
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
        foreach ($cursor as $key => $doc) {
            $doc = $this->connection->prepare($doc);

            $results[] = new Model($doc, array(
                'collection' => $this,
            ));
        }
        return $results;
    }

    public function filter($filter = null) {
        if (isset($filter)) {
            if (!isset($this->filter)) {
                $this->filter = array();
            }

            if (is_array($filter)) {

                $this->filter = $this->filter + $filter;
            } else {
                $this->filter = array('$id' => $filter);
            }
        }
    }

    public function find(array $filter = null) {
        $this->filter($filter);
        $result = $this->hydrate($this->connection->query($this));
        $this->filter = null;
        return $result;
    }

    public function findOne($filter = null) {
        $this->filter($filter);

        $cursor = $this->connection->query($this);

        $result = null;

        if ($o = $cursor->getNext()) {
            $hydrate = $this->hydrate(array($o));
            $result = $hydrate[0];
        }

        $this->filter = null;

        return $result;
    }

    public function newInstance($cloned = array()) {
        if ($cloned instanceof Model) {
            $cloned = $cloned->toArray(Model::FETCH_PUBLISHED);
        }
        return new Model($cloned, array('collection' => $this));
    }

    public function save(Model $model) {
        $result = $this->connection->save($this, $model);
        $this->filter = null;
        return $result;
    }

    public function remove($model) {
        $result = $this->connection->remove($this, $model);
        if ($result) {
            $model->reset();
        }
        $this->filter = NULL;
        return $result;
    }

    public function jsonSerialize() {
        return $this->clazz;
    }

}

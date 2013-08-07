<?php

namespace Norm;

use Doctrine\Common\Inflector\Inflector;
use Norm\Model;

class Collection {
    public $clazz;
    public $name;
    public $connection;

    public $filter;

    public function __construct(array $options = array()) {
        $this->clazz = $options['name'];
        $this->name = Inflector::tableize($this->clazz);
        $this->connection = $options['connection'];
    }

    public function hydrate($cursor) {
        $results = array();

        foreach ($cursor as $doc) {
            // $doc['_id'] = (string) $doc['_id'];
            $results[] = new Model($doc, array(
                'collection' => $this,
            ));
        }

        return $results;
    }

    public function find(array $filter = null) {
        if (isset($filter)) {
            $this->filter = $filter;
        }

        return $this->hydrate($this->connection->query($this));
    }

    public function findOne(array $filter = null) {
        if (isset($filter)) {
            $this->filter = $filter;
        }

        $cursor = $this->connection->query($this);

        if ($cursor->hasNext()) {
            $o = $cursor->getNext();
            return $this->hydrate([$o])[0];
        }
    }

    public function newInstance() {
        return new Model(array(), array('collection' => $this));
    }

    public function save(Model $model) {
        $this->connection->save($this, $model);
    }

    public function remove(Model $model) {
        $this->connection->remove($this, $model);
    }

}
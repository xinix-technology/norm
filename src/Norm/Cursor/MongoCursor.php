<?php

namespace Norm\Cursor;

class MongoCursor implements ICursor {

    protected $collection;

    protected $criteria;

    protected $sort;

    protected $skip;

    protected $limit;

    protected $cursor;

    public function __construct($collection = null) {
        $this->collection = $collection;

        $this->criteria = $this->prepareCriteria($collection->criteria);
    }

    public function getCursor() {
        if (is_null($this->cursor)) {
            $rawCollection = $this->collection->connection->getRaw()->{$this->collection->name};

            if (isset($this->criteria)) {
                $this->cursor = $rawCollection->find($this->criteria);
            } else {
                $this->cursor = $rawCollection->find();
            }
            if (isset($this->sort)) {
                $this->cursor->sort($this->sort);
            }
            if (isset($this->skip)) {
                $this->cursor->skip($this->skip);
            }

            if (isset($this->limit)) {
                $this->cursor->limit($this->limit);
            }
        }

        return $this->cursor;
    }

    public function prepareCriteria($criteria) {
        if (empty($criteria)) return null;

        $newCriteria = array();

        if (!empty($criteria['$id'])) {
            $newCriteria['_id'] = new \MongoId($criteria['$id']);
            unset($criteria['$id']);
        }

        foreach ($criteria as $key => $value) {
            $value = $value ?: NULL;
            $splitted = explode('!', $key);

            if ($splitted[0][0] == '$') {
                $splitted[0] = '_'.substr($splitted[0], 1);
            }

            if (count($splitted) > 1) {
                $newCriteria[$splitted[0]] = array( '$'.$splitted[1] => $value );
            } else {
                $newCriteria[$splitted[0]] = $value;
            }
        }

        return $newCriteria;
    }

    public function getNext() {
        return $this->getCursor()->getNext();
    }

    public function current() {
        return $this->getCursor()->current();
    }

    public function next() {
        $this->getCursor()->next();
    }

    public function key() {
        return $this->getCursor()->key();
    }

    public function valid() {
        return $this->getCursor()->valid();
    }

    public function rewind() {
        $this->getCursor()->rewind();
    }

    public function limit($num) {
        $this->limit = $num;
        return $this;
    }

    public function sort(array $fields) {
        $this->sort = $fields;
        return $this;
    }

    public function count($foundOnly = false) {
        return $this->getCursor()->count($foundOnly);
    }

    public function match($q) {
        if (is_null($q)) {
            return $this;
        }

        $orCriteria = array();

        $schema = $this->collection->schema();
        foreach ($schema as $key => $value) {
            $orCriteria[] = array($key => array('$regex' => new \MongoRegex("/$q/i")));
        }
        $this->criteria = array('$or' => $orCriteria);

        return $this;
    }

    public function skip($num) {
        $this->skip = $num;
        return $this;
    }
}
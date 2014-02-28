<?php

namespace Norm\Cursor;

class MongoCursor implements ICursor {

    protected $collection;

    protected $criteria;

    public function __construct($collection = null) {
        $this->collection = $collection;

        $this->criteria = $this->prepareCriteria($collection->criteria);
    }

    public function prepareCriteria($criteria) {
        // var_dump($criteria);

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
        throw new \Exception('Unimplemented yet!');
    }

    public function current() {
        throw new \Exception('Unimplemented yet!');
    }

    public function next() {
        throw new \Exception('Unimplemented yet!');
    }

    public function key() {
        throw new \Exception('Unimplemented yet!');
    }

    public function valid() {
        throw new \Exception('Unimplemented yet!');
    }

    public function rewind() {
        throw new \Exception('Unimplemented yet!');
    }

    public function limit($num) {
        throw new \Exception('Unimplemented yet!');
    }

    public function sort(array $fields) {
        throw new \Exception('Unimplemented yet!');
    }

    public function count() {
        throw new \Exception('Unimplemented yet!');
    }

    public function match($q) {
        throw new \Exception('Unimplemented yet!');
    }

    public function skip($num) {
        throw new \Exception('Unimplemented yet!');
    }
}
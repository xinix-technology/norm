<?php

namespace Norm;

use Norm\Cursor\ICursor;

class Cursor implements ICursor,  \JsonKit\JsonSerializer {

    protected $cursor;

    protected $collection;

    public function __construct($cursor, $collection) {
        $this->cursor = $cursor;
        $this->collection = $collection;
    }

    public function getNext() {
        $next = $this->cursor->getNext();
        if (isset($next)) {
            return $this->collection->attach($next);
        }
        return NULL;
    }

    public function current() {
        return $this->collection->attach($this->cursor->current());
    }

    public function next() {
        return $this->cursor->next();
    }

    public function key() {
        return $this->cursor->key();
    }

    public function valid() {
        return $this->cursor->valid();
    }

    public function rewind() {
        return $this->cursor->rewind();
    }

    public function toArray() {
        $result = array();
        foreach ($this as $key => $value) {
            $result[] = $value;
        }
        return $result;
    }

    public function limit($num) {
        $this->cursor->limit($num);
        return $this;
    }

    public function sort(array $fields) {
        $this->cursor->sort($fields);
        return $this;
    }

    public function count() {
        return $this->cursor->count(true);
    }

    public function match($q) {
        return $this->cursor->match($q);
    }

    public function skip($num) {
        $this->cursor->skip($num);
        return $this;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}
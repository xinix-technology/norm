<?php

namespace Norm\PDO;

class Cursor implements \Iterator {
    protected $statement;

    protected $current;

    public function __construct($statement) {
        $this->statement = $statement;
        $this->row = 0;
    }

    public function getNext() {
        if ($this->valid()) {
            return $this->current();
        }
    }

    public function current() {
        return $this->current;
    }

    public function next() {
        $this->row++;
    }

    public function key() {
        return $this->row;
    }

    public function valid() {
        $this->current = $this->statement->fetch(\PDO::FETCH_ASSOC);
        $valid = ($this->current !== false);
        return $valid;
    }

    public function rewind() {
        // TODO do nothing cannot rewind
        // throw new \Exception(__METHOD__.' not implemented yet!');
    }


}
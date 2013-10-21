<?php

namespace Norm\Mysql;

class Cursor  {
    protected $position = 0;
    protected $end = 0;
    protected $collection = array();

    public function __construct($array) {
        $this->end = count($array);
        $customArray = array();
        foreach ($array as $key => $value) {
            for($i = 0; $i < $this->end; $i++) {
                $customArray[$i][$key] = $value;
            }
        }
        $this->collection = $customArray;
        $this->position = 0;
    }

    public function hasNext() {
        if ($this->position < $this->end) {
            return true;
        } else {
            return false;
        }
    }

    public function next() {
        $this->position += 1;
    }

    private function current() {
        return $this->collection[$this->position];
    }

    public function getNext() {
        $retVal = $this->current();
        $this->next();
        return $retVal;
    }
}

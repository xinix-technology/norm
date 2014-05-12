<?php

namespace Norm\Cursor;

interface ICursor extends \Iterator
{
    public function getNext();

    public function current();

    public function next();

    public function key();

    public function valid();

    public function rewind();

    public function limit($num = null);

    public function sort(array $fields = array());

    public function count($force = true);

    public function match($q);

    public function skip($num = null);
}

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

    public function limit($num);

    public function sort(array $fields);

    public function count();

    public function match($q);

    public function skip($num);
}

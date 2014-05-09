<?php

namespace Norm\Cursor;

class QueryCursor implements ICursor
{
    protected $arr;

    public function __construct($arr)
    {
        $this->arr = $arr;
    }
}

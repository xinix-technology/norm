<?php

namespace Norm;

use Norm\Cursor\ICursor;
use JsonKit\JsonSerializer;

class Cursor implements ICursor, JsonSerializer
{

    protected $cursor;

    protected $collection;

    protected $links;

    protected $profiled = false;

    public function __construct($cursor, $collection)
    {
        $this->cursor = $cursor;
        $this->collection = $collection;
    }

    public function getNext()
    {
        if (!$this->profiled && $this->collection->connection->option('debug')) {
            f('profile.add', array(
                'section' => 'norm',
                'value' => $this->cursor->getQueryInfo()
            ));
            $this->profiled = true;
        }
        $next = $this->cursor->getNext();
        if (isset($next)) {
            return $this->collection->attach($next);
        }
        return null;
    }

    public function current()
    {
        if (!$this->profiled && $this->collection->connection->option('debug')) {
            f('profile.add', array('section' => 'norm', 'value' => array(
                'q' => $this->cursor->getQueryInfo(),
            )));
            $this->profiled = true;
        }
        $current = $this->cursor->current();
        if (isset($current)) {
            return $this->collection->attach($current);
        }
        return null;
    }

    public function next()
    {
        $this->cursor->next();
    }

    public function key()
    {
        return $this->cursor->key();
    }

    public function valid()
    {
        return $this->cursor->valid();
    }

    public function rewind()
    {
        return $this->cursor->rewind();
    }

    public function toArray($plain = false)
    {
        $result = array();
        foreach ($this as $key => $value) {
            if ($plain) {
                $result[] = $value->toArray();
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }

    public function limit($num = null)
    {
        if (func_num_args() === 0) {
            return $this->cursor->limit();
        }
        $this->cursor->limit($num);
        return $this;
    }

    public function sort(array $fields = array())
    {
        if (func_num_args() === 0) {
            return $this->cursor->sort();
        }
        $this->cursor->sort($fields);
        return $this;
    }

    public function count($foundOnly = false)
    {
        return $this->cursor->count($foundOnly);
    }

    public function match($q)
    {
        $this->cursor->match($q);
        return $this;
    }

    public function skip($num = null)
    {
        if (func_num_args() === 0) {
            return $this->cursor->skip();
        }
        $this->cursor->skip($num);
        return $this;
    }

    public function links()
    {
        return $this->links;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}

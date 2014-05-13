<?php

namespace Norm;

use JsonKit\JsonKit;
use Norm\Cursor\ICursor;
use Norm\Model;

class Cursor implements ICursor, \JsonKit\JsonSerializer
{

    protected $cursor;

    protected $collection;

    protected $links;

    public function __construct($cursor, $collection)
    {
        $this->cursor = $cursor;
        $this->collection = $collection;
    }

    public function getNext()
    {
        $next = $this->cursor->getNext();

        if (isset($next)) {
            return $this->collection->attach($next);
        }

        return null;
    }

    public function current()
    {
        return $this->collection->attach($this->cursor->current());
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

    public function toArray($normalize = false)
    {
        $result = array();

        foreach ($this as $key => $value) {
            if ($value instanceof Model) {
                $value = $value->toArray();
            }

            if ($normalize) {
                $value = $this->normalize($value);
            }

            $result[] = $value;
        }

        return $result;
    }

    public function normalize(array $object)
    {
        foreach ($object as $key => $value) {
            if (is_object($value)) {
                $object[$key] = $value->normalize();
            }
        }

        return $object;
    }

    public function limit($num)
    {
        $this->cursor->limit($num);
        return $this;
    }

    public function sort(array $fields)
    {
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

    public function skip($num)
    {
        $this->cursor->skip($num);
        return $this;
    }

    public function links()
    {
        return $this->links;
    }

    public function jsonSerialize($normalize = false)
    {
        return $this->toArray($normalize);
    }

    public function __toString()
    {
        return JsonKit::encode($this->toArray(true));
    }
}

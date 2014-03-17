<?php

namespace Norm;

use Norm\Cursor\ICursor;

class Cursor implements ICursor,  \JsonKit\JsonSerializer {

    protected $cursor;

    protected $collection;

    protected $links;

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
        $this->cursor->next();
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
        return $this->cursor->count();
    }

    public function match($q) {
        $this->cursor->match($q);
        return $this;
    }

    public function skip($num) {
        $this->cursor->skip($num);
        return $this;
    }

    public function paginate($page, $itemsPerPage = null) {
        if (is_null($page)) {
            $page = 1;
        }

        $page = (int) $page;

        if (is_null($itemsPerPage)) {
            $itemsPerPage = $this->collection->options['pagination'];
        }

        $totalItems   = $this->count();
        $totalPage    = round($totalItems / $itemsPerPage);
        $skip         = ($page > 1) ? ((($page - 1) * $itemsPerPage) + 1) : 0;
        $urls         = array();
        $limit        = ($skip > 0) ? ($skip + $itemsPerPage) : $itemsPerPage;

        $urls[] = array('skip' => 0, 'limit' => $itemsPerPage, 'active' => ($page == 1));

        for ($i = 1; $i < $totalPage; $i++) {
            $urls[$i] = array(
                'skip' => (($i * $itemsPerPage) + 1),
                'limit' => $itemsPerPage,
                'active' => (($page - 1) == $i)
            );
        }

        $this->links = $urls;


        return $this->skip($skip)->limit($itemsPerPage);
    }

    public function links() {
        return $this->links;
    }

    public function jsonSerialize() {
        return $this->toArray();
    }
}

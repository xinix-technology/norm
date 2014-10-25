<?php

namespace Norm\Cursor;

use Norm\Cursor;

class BonoCursor extends Cursor
{
    protected $buffer = array();

    protected $count = 0;

    protected $next;

    protected $isQueried = false;


    /**
     * @see Norm\Cursor::count()
     */
    public function count($foundOnly = false)
    {
        // TODO revisit me
        if ($foundOnly) {
            $this->rewind();
            return $this->count;
        } else {
            $this->rewind();
            return count($this->buffer);
        }
    }

    /**
     * @see Norm\Cursor::translateCriteria()
     */
    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

    /**
     * @see Norm\Cursor::current()
     */
    public function current()
    {
        $current = $this->next[1];
        return isset($current) ? $this->collection->attach($current) : null;
    }

    /**
     * @see Norm\Cursor::next()
     */
    public function next()
    {
        // Try to get the next element in our data buffer.
        $this->next = each($this->buffer);

        // Past the end of the data buffer
        if (false === $this->next && !$this->isQueried) {
            $this->isQueried = true;

            $connection = $this->collection->getConnection();

            $result = $connection->restGet($this);

            foreach ($result['entries'] as $k => $row) {
                $this->buffer[] = $row;
            }

            $this->count = count($this->buffer);

            $this->next = each($this->buffer);
        }
    }

    /**
     * @see Norm\Cursor::key()
     */
    public function key()
    {
        return $this->next[0];
    }

    /**
     * @see Norm\Cursor::valid()
     */
    public function valid()
    {
        if ($this->next) {
            return true;
        }
    }

    /**
     * @see Norm\Cursor::rewind()
     */
    public function rewind()
    {
        reset($this->buffer);
        $this->next();
    }
}

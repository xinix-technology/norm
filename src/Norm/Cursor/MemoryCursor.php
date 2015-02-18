<?php

namespace Norm\Cursor;

use Norm\Cursor;

class MemoryCursor extends Cursor
{
    /**
     * Buffer data held in memory
     * @var array
     */
    protected $buffer = array();

    /**
     * Next record read
     * @var boolean
     */
    protected $next = false;

    protected $isQueried = false;

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
            $buffer = $connection->getCollectionData($this->collection->getName());

            if (empty($this->criteria)) {
                $this->buffer = $buffer;
            } else {
                $this->buffer = array();

                foreach ($buffer as $k => $row) {
                    $match = true;
                    foreach ($this->criteria as $ckey => $cval) {
                        if ($row[$ckey] !== $cval) {
                            $match = false;
                        }
                    }
                    if ($match) {
                        $this->buffer[] = $row;
                    }
                }
            }

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
        return (false !== $this->next);
    }

    /**
     * @see Norm\Cursor::rewind()
     */
    public function rewind()
    {
        reset($this->buffer);
        $this->next();
    }

    /**
     * @see Norm\Cursor::count()
     */
    public function count($foundOnly = false)
    {
        // echo __METHOD__."\n";
        if ($foundOnly) {
            throw new \Exception('Unimplemented '.__METHOD__);
        } else {
            $this->rewind();
            return count($this->buffer);
        }
    }

    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

    public function distinct($key)
    {
        throw new \Exception('Unimplemented '.__METHOD__);
    }
}

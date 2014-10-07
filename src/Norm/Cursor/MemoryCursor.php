<?php

namespace Norm\Cursor;

use Norm\Cursor;

class MemoryCursor extends Cursor
{
    /**
     * Data held in memory
     * @var array
     */
    protected $data;

    /**
     * @see Norm\Cursor::current()
     */
    public function current()
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        $current = current($this->data);
        $current = $current ? $this->collection->attach($current) : null;

        return $current;
    }

    /**
     * @see Norm\Cursor::next()
     */
    public function next()
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        next($this->data);
    }

    /**
     * @see Norm\Cursor::key()
     */
    public function key()
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        return key($this->data);
    }

    /**
     * @see Norm\Cursor::valid()
     */
    public function valid()
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        $row = current($this->data);
        return ($row) ? true : false;
    }

    /**
     * @see Norm\Cursor::rewind()
     */
    public function rewind()
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        reset($this->data);
    }

    /**
     * @see Norm\Cursor::count()
     */
    public function count($foundOnly = false)
    {
        $this->initializeIfNotReadyYet();

        // echo __METHOD__."\n";
        if ($foundOnly) {
            throw new \Exception('Unimplemented '.__METHOD__);
        } else {
            return count($this->data);
        }
    }

    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

    /**
     * Method to initialize to prepare data if data not ready yet
     * @return void
     */
    protected function initializeIfNotReadyYet()
    {
        if (is_null($this->data)) {
            $connection = $this->collection->getConnection();
            $data = $connection->getCollectionData($this->collection->getName());

            if (empty($this->criteria)) {
                $this->data = $data;
            } else {
                $this->data = array();

                foreach ($data as $k => $row) {
                    $match = true;
                    foreach ($this->criteria as $ckey => $cval) {
                        if ($row[$ckey] !== $cval) {
                            $match = false;
                        }
                    }
                    if ($match) {
                        $this->data[] = $row;
                    }
                }
            }
        }
    }
}

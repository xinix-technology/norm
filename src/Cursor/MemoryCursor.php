<?php namespace Norm\Cursor;

use Exception;
use Norm\Cursor;

/**
 * Bono Cursor.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class MemoryCursor extends Cursor
{
    /**
     * Buffer data held in memory
     *
     * @var array
     */
    protected $buffer = array();

    /**
     * Next record read
     *
     * @var boolean
     */
    protected $next = false;

    /**
     * A property shows us whether document has been queried or not.
     *
     * @var boolean
     */
    protected $isQueried = false;

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $current = $this->next[1];
        return isset($current) ? $this->collection->attach($current) : null;
    }

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->next[0];
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return (false !== $this->next);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        reset($this->buffer);

        $this->next();
    }

    /**
     * {@inheritDoc}
     */
    public function count($foundOnly = false)
    {
        if ($foundOnly) {
            throw new Exception('Unimplemented '.__METHOD__);
        } else {
            $this->rewind();

            return count($this->buffer);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

    /**
     * {@inheritDoc}
     */
    public function distinct($key)
    {
        throw new Exception('Unimplemented '.__METHOD__);
    }
}

<?php namespace Norm\Cursor;

use Norm\Cursor;

/**
 * Bono Cursor.
 *
 * @author    Ganesha <reekoheek@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class BonoCursor extends Cursor
{
    /**
     * Data buffer
     *
     * @var array
     */
    protected $buffer = array();

    /**
     * Length of the document haystack.
     *
     * @var integer
     */
    protected $count = 0;

    /**
     * Next document in haystack.
     *
     * @var mixed
     */
    protected $next;

    /**
     * A property shows us whether document has been queried or not.
     *
     * @var boolean
     */
    protected $isQueried = false;

    /**
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

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

            $result = $connection->restGet($this);

            foreach ($result['entries'] as $k => $row) {
                $this->buffer[] = $row;
            }

            $this->count = count($this->buffer);

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
        if ($this->next) {
            return true;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        reset($this->buffer);

        $this->next();
    }
}

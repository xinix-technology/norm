<?php namespace Norm\Cursor;

use Exception;
use Norm\Cursor;

/**
 * Flat File Cursor.
 *
 * @author    Krisan Alfa Timur <krisan47@gmail.com>
 * @copyright 2013 PT Sagara Xinix Solusitama
 * @link      http://xinix.co.id/products/norm Norm
 * @license   https://raw.github.com/xinix-technology/norm/master/LICENSE
 */
class FlatFileCursor extends Cursor
{
    /**
     * Index in current active cursor.
     *
     * @var integer
     */
    protected $index = -1;

    /**
     * Rows in cursor.
     *
     * @var null
     */
    protected $rows = null;

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->collection->attach($this->rows[$this->index]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getNext()
    {
        $this->index++;

        return $this->current();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        return $this->getNext();
    }

    /**
     * Execute a query.
     *
     * @return void
     */
    protected function execute()
    {
        $connection = $this->collection->getConnection();

        $rows = $connection->getCollectionData($this->collection->getName(), $this->criteria);

        $this->rows = array_slice($rows, $this->skip, $this->limit);

        foreach ((array) $this->sorts as $field => $flag) {
            usort($this->rows, function($a, $b) use ($field, $flag) {
                if (isset($a[$field])) {
                    $aValue = utf8_encode(filter_var((string) $a[$field], FILTER_SANITIZE_STRING));
                    $bValue = utf8_encode(filter_var((string) $b[$field], FILTER_SANITIZE_STRING));

                    return strcasecmp($aValue, $bValue) * (int) $flag;
                }
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        if (is_null($this->rows)) {
            $this->execute();
        }

        return isset($this->rows[$this->index]);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->index = -1;

        $this->next();
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

    /**
     * {@inheritDoc}
     */
    public function count($foundOnly = false)
    {
        if (is_null($this->rows)) {
            $this->execute();
        } else if (! $foundOnly) {
            $this->limit = null;
            $this->skip = null;
            $this->criteria = null;

            $this->execute();
        }

        return count($this->rows);
    }
}

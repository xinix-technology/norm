<?php namespace Norm\Cursor;

use Norm\Cursor;
use Exception;

class FlatFileCursor extends Cursor
{
    protected $index = -1;

    protected $rows = null;

    public function current()
    {
        if ($this->valid()) {
            return $this->collection->attach($this->rows[$this->index]);
        }

        return null;
    }

    public function getNext()
    {
        $this->index++;

        return $this->current();
    }

    public function next()
    {
        return $this->getNext();
    }

    private function execute()
    {
        $connection = $this->collection->getConnection();

        $rows = $connection->getCollectionData($this->collection->getName(), $this->criteria);

        $this->rows = array_slice($rows, $this->skip, $this->limit);

        $this->sorts = $this->sorts ?: array();

        foreach ($this->sorts as $field => $flag) {
            usort($this->rows, function($a, $b) use ($field, $flag) {
                if (isset($a[$field])) {
                    $aValue = utf8_encode(filter_var((string) $a[$field], FILTER_SANITIZE_STRING));
                    $bValue = utf8_encode(filter_var((string) $b[$field], FILTER_SANITIZE_STRING));

                    return strcasecmp($aValue, $bValue) * (int) $flag;
                }
            });
        }
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        if (is_null($this->rows)) {
            $this->execute();
        }

        return isset($this->rows[$this->index]);
    }

    public function rewind()
    {
        $this->index = -1;

        $this->next();
    }

    public function translateCriteria(array $criteria = array())
    {
        return $criteria;
    }

    public function distinct($key)
    {
        throw new Exception('Unimplemented '.__METHOD__);
    }

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

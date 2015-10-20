<?php namespace Norm;

use Iterator;
use Countable;
use InvalidArgumentException;
use JsonKit\JsonSerializer;

/**
 * Cursor abstract class.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2015 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Cursor implements Iterator, Countable, JsonSerializer
{
    const SORT_ASC = 1;

    const SORT_DESC = -1;

    /**
     * Norm Collection implementation
     *
     * @var Norm\Collection
     */
    protected $collection;

    /**
     * Criteria
     *
     * @var array
     */
    protected $criteria;

    /**
     * Limit of document we want to fetch from database.
     *
     * @var int
     */
    protected $limit = 0;

    /**
     * Number of document we want to skip when fetching a document.
     *
     * @var int
     */
    protected $skip = 0;

    /**
     * Sorts criteria
     *
     * @var array
     */
    protected $sorts;

    /**
     * Query match
     * @var string
     */
    protected $match;

    protected $position = 0;

    protected $context;

    /**
     * Constructor
     *
     * @param Norm\Collection $collection
     *
     * @param array $criteria
     */
    public function __construct(Collection $collection, array $criteria = [])
    {
        $this->collection = $collection;
        $this->criteria = $criteria;
    }

    // getter / setter *********************************************************

    public function getCollectionId()
    {
        return $this->collection->getId();
    }

    /**
     * Getter for criteria
     *
     * @return array
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * When argument specified will set new limit otherwise will return existing limit
     *
     * @param integer $limit
     *
     * @return mixed When argument specified will return limit otherwise return chainable object
     */
    public function getLimit()
    {
        return $this->limit;
    }

    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * When argument specified will set new skip otherwise will return existing skip
     *
     * @param integer $skip
     *
     * @return mixed When argument specified will return skip otherwise return chainable object
     */
    public function getSkip()
    {
        return $this->skip;
    }

    public function skip($skip)
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * When argument specified will set new sorts otherwise will return existing sorts
     *
     * @param array $sorts
     *
     * @return mixed When argument specified will return sorts otherwise return chainable object
     */
    public function getSort()
    {
        return $this->sorts;
    }

    public function sort(array $sorts)
    {
        $this->sorts = $sorts;

        return $this;
    }

    /**
     * Set query to match on every field exists in schema. Beware this will override criteria
     *
     * @param string $q String to query
     *
     * @return Norm\Cursor Chainable object
     */
    public function getMatch()
    {
        return $this->match;
    }

    public function match($match)
    {
        $this->match = $match;

        return $this;
    }

    // accessor ****************************************************************

    /**
     * Serialize instance to json
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Extract data into array of models.
     *
     * @param boolean $plain When true will return array of associative array.
     *
     * @return array
     */
    public function toArray($plain = false)
    {
        $result = [];
        foreach ($this as $key => $value) {
            if ($plain) {
                $result[] = $value->toArray();
            } else {
                // throw new \Exception('why collection unmarshall here?');
                // $result[] = $this->collection->unmarshall($value);
                $result[] = $value;
            }
        }
        return $result;
    }

    // behavior ****************************************************************

    public function getContext()
    {
        if (is_null($this->context)) {
            $this->context = $this->collection->cursorFetch($this);
        }
        return $this->context;
    }

    /**
     * Get specific distinct key from cursor result
     *
     * @param string $key
     *
     * @return array
     */
    public function distinct($key)
    {
        return $this->collection->cursorDistinct($this, $key);
    }

    public function current()
    {
        return $this->collection->cursorRead($this->getContext(), $this->position);
    }

    public function next()
    {
        if ($this->valid()) {
            $this->position++;
        }
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {

        $row = $this->current();
        return (is_null($row)) ? false : true;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function count()
    {
        return $this->size(true);
    }

    public function size($respectLimitSkip = false)
    {
        return $this->collection->cursorSize($this, $respectLimitSkip);
    }

    public function first()
    {
        $this->rewind();
        return $this->current();
    }
}

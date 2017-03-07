<?php
namespace Norm;

use Exception;
use InvalidArgumentException;
use Iterator;
use Countable;
use JsonKit\JsonSerializer;
use Norm\Normable;

/**
 * Cursor abstract class.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
class Cursor extends Normable implements Iterator, Countable, JsonSerializer
{
    const SORT_ASC = 1;

    const SORT_DESC = -1;

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
    protected $limit = -1;

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
    protected $sorts = [];

    /**
     * Query match
     * @var string
     */
    protected $match;

    /**
     * [$position description]
     * @var integer
     */
    protected $position = 0;

    /**
     * [$context description]
     * @var array
     */
    protected $context;

    /**
     * [$current description]
     * @var [type]
     */
    protected $current = [-1, null];

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
        $this->repository = $collection->getRepository();
        $this->criteria = $criteria;
    }

    // getter / setter *********************************************************

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

    public function setLimit($limit)
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

    public function setSkip($skip)
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

    public function setSort(array $sorts)
    {
        $this->sorts = $sorts;

        return $this;
    }

    public function distinct($key)
    {
        return $this->collection->distinct($this, $key);
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
                $result[] = $value;
            }
        }
        return $result;
    }

    // behavior ****************************************************************

    /**
     * [getContext description]
     * @return mixed [description]
     */
    public function getContext()
    {
        return $this->context;
    }

    public function setContext($context)
    {
        $this->context = $context;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * [current description]
     * @return Model [description]
     */
    public function current()
    {
        if ($this->current[0] !== $this->position) {
            $this->current = [ $this->position, $this->collection->read($this) ];
        }

        return $this->current[1];
    }

    /**
     * [next description]
     * @return function [description]
     */
    public function next()
    {
        // if ($this->valid()) {
        $this->position++;
        // }
    }

    /**
     * [prev description]
     * @return [type] [description]
     */
    public function prev()
    {
        if ($this->position > 0) {
            $this->position--;
        }
    }

    /**
     * [key description]
     * @return int [description]
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * [valid description]
     * @return bool [description]
     */
    public function valid()
    {
        return null !== $this->current();
    }

    /**
     * [rewind description]
     * @return [type] [description]
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * [count description]
     * @return int [description]
     */
    public function count()
    {
        return $this->size(true);
    }

    /**
     * [size description]
     * @param  boolean $respectLimitSkip [description]
     * @return int                       [description]
     */
    public function size($respectLimitSkip = false)
    {
        return $this->collection->size($this, $respectLimitSkip);
    }

    /**
     * [remove description]
     * @param  array  $options [description]
     * @return [type]          [description]
     */
    public function remove(array $options = [])
    {
        return $this->collection->remove($this, $options);
    }

    /**
     * [first description]
     * @return Model [description]
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    public function __debugInfo()
    {
        return [
            'criteria' => $this->criteria,
            'skip' => $this->skip,
            'limit' => $this->limit,
            'sort' => $this->sorts,
        ];
    }
}

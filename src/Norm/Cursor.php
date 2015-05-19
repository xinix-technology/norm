<?php namespace Norm;

use JsonKit\JsonSerializer;

/**
 * Cursor abstract class.
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2013 PT Sagara Xinix Solusitama
 * @link        http://xinix.co.id/products/norm Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Cursor implements \Iterator, \Countable, JsonSerializer
{
    /**
     * Collection implementation
     *
     * @var \Norm\Collection
     */
    protected $collection;

    /**
     * Norm Connection implementation
     *
     * @var \Norm\Connection
     */
    protected $connection;

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
    protected $limit;

    /**
     * Number of document we want to skip when fetching a document.
     *
     * @var int
     */
    protected $skip;

    /**
     * Sorts criteria
     *
     * @var array
     */
    protected $sorts;

    /**
     * Constructor
     *
     * @param \Norm\Collection $collection
     *
     * @param array $criteria
     */
    public function __construct(Collection $collection, $criteria = array())
    {
        $this->collection = $collection;
        $this->connection = $collection->getConnection();

        if (is_null($this->connection)) {
            throw new \Exception('[Norm/Cursor] Collection does not have connection, check your configuration!');
        }

        if ($criteria === null) {
            $criteria = array();
        }

        $this->criteria = $this->translateCriteria($criteria);
    }

    /**
     * Getter for collection
     *
     * @return Norm\Collection
     */
    public function getCollection()
    {
        return $this->collection;
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
     * Return the next object to which this cursor points, and advance the cursor
     *
     * @return \Norm\Model
     */
    public function getNext()
    {
        $this->next();

        return $this->current();
    }

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
     * When argument specified will set new limit otherwise will return existing limit
     *
     * @param integer $limit
     *
     * @return mixed When argument specified will return limit otherwise return chainable object
     */
    public function limit($limit = 0)
    {
        if (func_num_args() === 0) {
            return $this->limit;
        }

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
    public function skip($skip = 0)
    {
        if (func_num_args() === 0) {
            return $this->skip;
        }

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
    public function sort(array $sorts = array())
    {
        if (func_num_args() === 0) {
            return $this->sorts;
        }

        $this->sorts = array();

        foreach ($sorts as $key => $value) {
            if ($key[0] === '$') {
                $key[0] = '_';
            }

            $this->sorts[$key] = $value;
        }

        return $this;
    }

    /**
     * Set query to match on every field exists in schema. Beware this will override criteria
     *
     * @param string $q String to query
     *
     * @return \Norm\Cursor Chainable object
     */
    public function match($q)
    {
        if (is_null($q)) {
            return $this;
        }

        $orCriteria = array();

        $schema = $this->collection->schema();

        if (empty($schema)) {
            throw new \Exception('[Norm\Cursor] Cannot use match for schemaless collection');
        }

        foreach ($schema as $key => $value) {
            $orCriteria[] = array($key.'!like' => $q);
        }

        $this->criteria = $this->translateCriteria(array('!or' => $orCriteria));

        return $this;
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
        $result = array();

        foreach ($this as $key => $value) {
            if ($plain) {
                $result[] = $value->toArray();
            } else {
                $result[] = $this->connection->unmarshall($value);
            }
        }

        return $result;
    }

    /**
     * Return number of documents available. When foundOnly true will return found document only
     *
     * @param boolean $foundOnly
     *
     * @return integer
     */
    abstract public function count($foundOnly = false);

    /**
     * Translate criteria into accepted criteria for specific system.
     *
     * @param array $criteria Norm criteria
     *
     * @return mixed Specific system criteria
     */
    abstract public function translateCriteria(array $criteria = array());

    /**
     * Get specific distinct key from cursor result
     *
     * @param string $key
     *
     * @return array
     */
    abstract public function distinct($key);
}

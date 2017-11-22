<?php

namespace Norm;

use Norm\Exception\NormException;
use Norm\Type\Marshallable;

/**
 * Base class for connection instance
 *
 * @author      Ganesha <reekoheek@gmail.com>
 * @copyright   2016 PT Sagara Xinix Solusitama
 * @link        http://sagara.id/p/product Norm
 * @license     https://raw.github.com/xinix-technology/norm/master/LICENSE
 * @package     Norm
 */
abstract class Connection extends Normable
{
    protected static $generatedId = 0;

    protected $id;

    protected $primaryKey = 'id';

    public static function generateId()
    {
        return 'connection-' . static::$generatedId++;
    }

    public function __construct(Repository $repository, $id = null)
    {
        parent::__construct($repository);

        $this->id = null === $id ? Connection::generateId() : $id;

        $repository->addConnection($this);
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Unmarshall single assoc from data source to norm friendly associative array.
     * The unmarshall process is necessary due to different data type provided
     * by data source. Proper unmarshall will make sure data from data source
     * that will be consumed by Norm in the accepted form of data.
     *
     * @see Norm\Connection::marshall()
     *
     * @param mixed $assoc Object from data source
     *
     * @return array Friendly norm data
     */
    public function unmarshall(array $object)
    {
        $result = [];
        foreach ($object as $key => $value) {
            list($k, $v) = $this->unmarshallKV($key, $value);
            if (null !== $k) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    public function unmarshallKV($key, $value)
    {
        if ($this->primaryKey === $key) {
            return [ '$id', $value ];
        } elseif ('_' === substr($key, 0, 1)) {
            $key = '$'.substr($key, 1);
        }

        return [ $key, $value ];
    }

    /**
     * Marshal single object from norm to the proper data accepted by data source.
     * Sometimes data source expects object to be persisted to it in specific form,
     * this method will transform associative array from Norm into this specific form.
     *
     * @see Norm\Connection::unmarshall()
     *
     * @param mixed $object Norm data
     *
     * @return mixed Friendly data source object
     */
    public function marshall(array $object)
    {
        $result = [];
        foreach ($object as $key => $value) {
            if ('$id' === $key || '$type' === $key) {
                continue;
            }
            list($k, $v) = $this->marshallKV($key, $value);
            $result[$k] = $v;
        }
        return $result;
    }

    public function marshallKV($key, $value)
    {
        if ('$id' === $key) {
            return [ $this->primaryKey, $value ];
        } elseif ('$' === substr($key, 0, 1)) {
            $key = '_'.substr($key, 1);
        }

        if ($value instanceof Marshallable) {
            $value = $value->marshall();
        }

        return [ $key, $value ];
    }

    /**
     * [marshallCriteria description]
     * @param  array  $criteria [description]
     * @return [type]           [description]
     */
    public function marshallCriteria(array $criteria) {
        $result = [];
        foreach ($criteria as $key => $value) {
            list($k, $v) = $this->marshallKV($key, $value);
            $result[$k] = $v;
        }
        return $result;
    }

    /**
     * Getter for context-type of connection
     *
     * @return mixed context-type of connection
     */
    abstract public function getContext();

    /**
     * Persist specified attributes with current connection
     *
     * @param string        $collectionId Collection name or instance
     * @param array         $attributes Attributes to persist
     *
     * @return array Attributes persisted
     */
    abstract public function persist($collectionId, array $row);

    /**
     * [remove description]
     * @param  Cursor $cursor [description]
     * @return [type]         [description]
     */
    abstract public function remove(Cursor $cursor);

    /**
     * [distinct description]
     * @param  Cursor $cursor [description]
     * @param  [type] $key    [description]
     * @return [type]         [description]
     */
    abstract public function distinct(Cursor $cursor, $key);

    /**
     * [fetch description]
     * @param  Cursor $cursor [description]
     * @return [type]         [description]
     */
    // abstract public function fetch(Cursor $cursor);

    /**
     * [size description]
     * @param  Cursor  $cursor        [description]
     * @param  boolean $withLimitSkip [description]
     * @return [type]                 [description]
     */
    abstract public function size(Cursor $cursor, $withLimitSkip = false);

    /**
     * [read description]
     * @param  Cursor  $cursor   [description]
     * @return [type]            [description]
     */
    abstract public function read(Cursor $cursor);
}

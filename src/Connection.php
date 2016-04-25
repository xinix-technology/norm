<?php

namespace Norm;

use Norm\Exception\NormException;
use Norm\Type\DateTime;
use Norm\Type\ArrayList;
use Norm\Cursor;

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
    /**
     * [$id description]
     * @var string
     */
    protected $id;

    /**
     * [__construct description]
     * @param string $id [description]
     */
    public function __construct(Repository $repository = null, $id = 'main')
    {
        if (!is_string($id)) {
            throw new NormException('Connection must specified id');
        }

        parent::__construct($repository);

        $this->id = $id;
    }

    public function setRepository(Repository $repository)
    {
        $this->parent = $repository;
        return $this;
    }

    /**
     * [multiPersist description]
     * @param  string $collectionId [description]
     * @param  array  $rows         [description]
     * @return [type]               [description]
     */
    // public function multiPersist($collectionId, array $rows)
    // {
    //     return array_map(function ($row) {
    //         return $this->persist($collectionId, $row);
    //     }, $rows);
    // }

    /**
     * [getId description]
     * @return string [description]
     */
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
    public function unmarshall(array $assoc)
    {
        $result = [];
        foreach ($assoc as $key => $value) {
            if ($key[0] === '_') {
                $key[0] = '$';
                $result[$key] = $value;
            } elseif ($key === 'id') {
                $result['$id'] = $assoc['id'];
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
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
    public function marshall($object, $primaryKey = null)
    {
        if (is_array($object)) {
            $result = [];

            foreach ($object as $key => $value) {
                if ('$' === substr($key, 0, 1)) {
                    if ((null === $primaryKey && '$id' === $key) || '$type' === $key) {
                        continue;
                    } elseif ('$id' === $key) {
                        $result[$primaryKey] = $this->marshall($value);
                    } else {
                        $result['_'.substr($key, 1)] = $this->marshall($value);
                    }
                } else {
                    $result[$key] = $this->marshall($value);
                }
            }

            return $result;
        } elseif ($object instanceof DateTime) {
            return $object->format('c');
        } elseif ($object instanceof ArrayList) {
            return json_encode($object->toArray());
        } elseif (method_exists($object, 'marshall')) {
            return $object->marshall();
        }

        return $object;
    }

    public function factory($collectionId, $connectionId = '')
    {
        return $this->parent->factory($collectionId, $connectionId ?: $this->id);
    }

    /**
     * Getter for raw-type of connection
     *
     * @return mixed Raw-type of connection
     */
    abstract public function getRaw();

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
     * @return [type]         [description]
     */
    abstract public function distinct(Cursor $cursor);

    /**
     * [fetch description]
     * @param  Cursor $cursor [description]
     * @return [type]         [description]
     */
    abstract public function fetch(Cursor $cursor);

    /**
     * [size description]
     * @param  Cursor  $cursor        [description]
     * @param  boolean $withLimitSkip [description]
     * @return [type]                 [description]
     */
    abstract public function size(Cursor $cursor, $withLimitSkip = false);

    /**
     * [read description]
     * @param  mixed   $context  [description]
     * @param  integer $position [description]
     * @return [type]            [description]
     */
    abstract public function read($context, $position = 0);
}

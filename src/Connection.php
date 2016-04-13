<?php

namespace Norm;

use Norm\Exception\NormException;
use Traversable;
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
abstract class Connection
{
    /**
     * [$id description]
     * @var string
     */
    protected $id;

    /**
     * [$raw description]
     * @var mixed
     */
    protected $raw;

    /**
     * [__construct description]
     * @param string $id [description]
     */
    public function __construct($id = 'main')
    {
        if (!is_string($id)) {
            throw new NormException('Connection must specified id');
        }

        $this->id = $id;
    }

    /**
     * [multiPersist description]
     * @param  string $collectionId [description]
     * @param  array  $rows         [description]
     * @return [type]               [description]
     */
    public function multiPersist($collectionId, array $rows)
    {
        return array_map(function ($row) {
            return $this->persist($collectionId, $row);
        }, $rows);
    }

    /**
     * Getter for raw-type of connection
     *
     * @return mixed Raw-type of connection
     */
    public function getRaw()
    {
        return $this->raw;
    }

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
    public function unmarshall($assoc)
    {
        if (!is_array($assoc) && !($assoc instanceof Traversable)) {
            throw new NormException('Unmarshall only accept array or traversable');
        }

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
                if ($key[0] === '$') {
                    if (($key === '$id' && is_null($primaryKey)) || $key === '$type') {
                        continue;
                    } elseif ($key === '$id') {
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
     * @param  string         $collectionId [description]
     * @param  string|integer $rowId        [description]
     * @return [type]                       [description]
     */
    abstract public function remove($collectionId, $rowId);

    /**
     * [cursorDistinct description]
     * @param  Cursor $cursor [description]
     * @return [type]         [description]
     */
    abstract public function cursorDistinct(Cursor $cursor);

    /**
     * [cursorFetch description]
     * @param  Cursor $cursor [description]
     * @return [type]         [description]
     */
    abstract public function cursorFetch(Cursor $cursor);

    /**
     * [cursorSize description]
     * @param  Cursor  $cursor        [description]
     * @param  boolean $withLimitSkip [description]
     * @return [type]                 [description]
     */
    abstract public function cursorSize(Cursor $cursor, $withLimitSkip = false);

    /**
     * [cursorRead description]
     * @param  mixed   $context  [description]
     * @param  integer $position [description]
     * @return [type]            [description]
     */
    abstract public function cursorRead($context, $position = 0);
}
